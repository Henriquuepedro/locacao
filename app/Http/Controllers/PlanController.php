<?php

namespace App\Http\Controllers;

use App\Exceptions\MercadoPagoException;
use App\Models\Company;
use App\Models\Plan;
use App\Models\PlanHistory;
use App\Models\PlanPayment;
use App\Models\User;
use DateTime;
use DateTimeZone;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Common\RequestOptions;
use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Normalizer;
use Ramsey\Uuid\Uuid;
use MercadoPago\Client\PreApproval\PreApprovalClient;

class PlanController extends Controller
{
    private Plan $plan;
    private User $user;
    private Company $company;
    private PlanPayment $plan_payment;
    private PlanHistory $plan_history;
    private MercadoPagoException $mercado_pago_exception;

    public function __construct()
    {
        $this->user = new User();
        $this->plan = new Plan();
        $this->company = new Company();
        $this->plan_payment = new PlanPayment();
        $this->plan_history = new PlanHistory();
        $this->mercado_pago_exception = new MercadoPagoException();
    }

    public function index(): Factory|View|Application
    {
        return view('plan.index');
    }

    public function confirm(int $plan): Factory|View|RedirectResponse|Application
    {
        $plan = $this->plan->getById($plan);
        $company_id = Auth::user()->__get('company_id');

        if (!$plan) {
            return redirect()->route('plan.index')
                ->with('warning', "Plano não encontrado!");
        }

        $now    = new DateTimeImmutable("now");
        $config = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText(config('app.key')));

        $token = $config->builder()
            ->issuedBy(url()->current())
            ->withHeader('iss', url()->current())
            ->permittedFor(url()->route('plan.insert', array('plan' => $plan)))
            ->issuedAt($now)
            ->expiresAt($now->modify('+12 hours'))
            ->withClaim('uid', 1)
            ->withClaim('plan_value', (float)$plan->value)
            ->withClaim('plan_id', $plan->id)
            ->withClaim('plan_name', $plan->name)
            ->getToken($config->signer(), $config->signingKey());

        $tokenStr = $token->toString();

        $company_data = $this->company->getCompany($company_id);
        $company_data->first_company_name = explode(' ', $company_data->name)[0];
        $company_data->last_company_name  = str_replace("$company_data->first_company_name ", '', $company_data->name);

        $idempotency_key = Uuid::uuid4()->toString();

        return view('plan.confirm', compact('plan', 'tokenStr', 'company_data', 'idempotency_key'));
    }

    public function confirm_subscription_payment(int $plan): Factory|View|RedirectResponse|Application
    {
        $plan = $this->plan->getById($plan);
        $company_id = Auth::user()->__get('company_id');

        if (!$plan) {
            return redirect()->route('plan.index')
                ->with('warning', "Plano não encontrado!");
        }

        if (empty($plan->plan_id_gateway)) {
            return redirect()->route('plan.index')
                ->with('warning', "Código do plano no gateway de pagamento não encontrado.");
        }

        $now    = new DateTimeImmutable("now");
        $config = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText(config('app.key')));

        if (!empty($plan->discount_subscription)) {
            $plan->value -= ($plan->value * ($plan->discount_subscription / 100));
        }

        $plan->value = roundDecimal($plan->value, 2, false);

        $token = $config->builder()
            ->issuedBy(url()->current())
            ->withHeader('iss', url()->current())
            ->permittedFor(url()->route('plan.insert', array('plan' => $plan)))
            ->issuedAt($now)
            ->expiresAt($now->modify('+12 hours'))
            ->withClaim('uid', 1)
            ->withClaim('plan_value', $plan->value)
            ->withClaim('plan_id', $plan->id)
            ->withClaim('plan_name', $plan->name)
            ->getToken($config->signer(), $config->signingKey());

        $tokenStr = $token->toString();

        $company_data = $this->company->getCompany($company_id);
        $company_data->first_company_name = explode(' ', $company_data->name)[0];
        $company_data->last_company_name  = str_replace("$company_data->first_company_name ", '', $company_data->name);

        $idempotency_key = Uuid::uuid4()->toString();

        return view('plan.confirm_subscription_payment', compact('plan', 'tokenStr', 'company_data', 'idempotency_key'));
    }

    public function request(): Factory|View|Application
    {
        return view('plan.request');
    }

    public function getPlans(int $type = 1): JsonResponse
    {
        return response()->json($this->plan->getByMonthTime($type));
    }

    public function insert(int $plan, Request $request)
    {
        $company_id = $request->user()->company_id;

        try {
            $config = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText(config('app.key')));
            $clock = new SystemClock(new DateTimeZone(TIMEZONE_DEFAULT));
            assert($config instanceof Configuration);

            $token  = $config->parser()->parse($request->input('token_plan'));
            $claims = $token->claims();
            assert($token instanceof UnencryptedToken);

            $config->setValidationConstraints(
                new LooseValidAt($clock),
                new PermittedFor(url()->route('plan.insert', array('plan' => $plan)))
            );

            $constraints = $config->validationConstraints();

            if (!$config->validator()->validate($token, ...$constraints)) {
                return response()->json(['errors' => 'Nao foi possível identificar o plano de pagamento. Recarregue a página.'], 400);
            }

            $plan_id    = $claims->get('plan_id');
            $plan_value = $claims->get('plan_value');

            MercadoPagoConfig::setAccessToken(env('MP_ACCESS_TOKEN'));

            $check_payment_method   = $request->input('payment_method_id');
            $plan_data              = $this->plan->getById($plan);
            $code_payment           = getKeyRandom();

            if (empty($plan_data->plan_id_gateway)) {
                return response()->json(['errors' => 'Código do plano no gateway de pagamento não encontrado.'], 400);
            }

            // É cartão
            if ($request->input('token') && $request->input('issuer_id')) {
                $check_payment_method = 'card';
            }

            if ($request->has('subscription_payment')) {
                if (!empty($plan_data->discount_subscription)) {
                    $plan_data->value -= ($plan_data->value * ($plan_data->discount_subscription / 100));
                }
            }
            $plan_data->value = roundDecimal($plan_data->value, 2, false);

            if (
                $plan_data->value != roundDecimal($plan_value, 2, false)
            ) {
                return response()->json(['errors' => 'Valor não corresponde ao valor do plano selecionado.'], 400);
            }

            if ($plan_id != $plan) {
                return response()->json(['errors' => 'Plano não reconhecido.'], 400);
            }

            $createRequest = $this->makeDataToPay($plan, $code_payment, $request);

            $client = new PaymentClient();
            $request_options = new RequestOptions();
            $request_options->setCustomHeaders(["X-Idempotency-Key: {$request->input('idempotency_key')}"]);
            $request_options->setCustomHeaders(["X-meli-session-id: {$request->input('device_id')}"]);

            if ($request->has('subscription_payment')) {
                $preApproval = new PreApprovalClient();
                $payment = $preApproval->create($createRequest, $request_options);
            } else {
                $payment = $client->create($createRequest, $request_options);
            }

            $this->validatePaymentResult($payment);
            Log::info("Payment created successfully to the company $company_id to the plan $plan.", [
                'request'  => $createRequest,
                'response' => $payment
            ]);
        } catch (MPApiException $exception) {
            $error_message = $exception->getApiResponse()->getContent();
            Log::error("[MPApiException] Payment doesn't created to the company $company_id to the plan $plan.", [
                'request'   => $createRequest ?? [],
                'response'  => $error_message,
                'trace'     => $exception->getTraceAsString()
            ]);
            return response()->json(['errors' => $error_message['message']], 400);
        } catch (Exception $exception) {
            $error_message = $exception->getMessage();
            Log::error("[Exception] Payment doesn't created to the company $company_id to the plan $plan.", [
                'request'   => $createRequest ?? [],
                'response'  => $error_message,
                'trace'     => $exception->getTraceAsString()
            ]);
            return response()->json(['errors' => $error_message], 400);
        }

        // Taxas: https://www.mercadopago.com.br/ajuda/custo-receber-pagamentos_220
        $netAmount = $request->has('subscription_payment') ? $payment->auto_recurring->transaction_amount : $payment->transaction_amount;
        if (!empty($payment->transaction_details->net_received_amount)) {
            $netAmount = $payment->transaction_details->net_received_amount;
        } else if ($check_payment_method === 'pix') {
            $netAmount = $payment->transaction_amount * 0.99; // taxa de 0.99% no pix
        } elseif (in_array($check_payment_method, array('bolbradesco', 'pec'))) {
            $netAmount = $payment->transaction_amount - 3.49; // taxa de R$ 3.49 no boleto
        } elseif ($request->has('subscription_payment') || $check_payment_method === 'card') {
            if ($request->has('subscription_payment') || $payment->payment_type_id === 'credit_card') {
                $netAmount -= ($netAmount * (3.98 / 100)); // taxa de 3.98% no cartão de crédito
            } else {
                $netAmount -= ($netAmount * (3.99 / 100)); // taxa de 3.99% no cartão
            }
        }

        $dateOfExpiration = formatDateInternational($request->has('subscription_payment') ? $payment->auto_recurring->end_date : ($payment->date_of_expiration ?? null));

        $this->plan_payment->insert(array(
            'id_transaction'    => $payment->id,
            'code_payment'      => $code_payment,
            'link_billet'       => $request->has('subscription_payment') ? ($payment->init_point ?? null) : ($payment->transaction_details->external_resource_url ?? null),
            'barcode_billet'    => $payment->transaction_details->digitable_line ?? null,
            'date_of_expiration'=> $dateOfExpiration,
            'key_pix'           => $payment->point_of_interaction->transaction_data->qr_code ?? null,
            'base64_key_pix'    => $payment->point_of_interaction->transaction_data->qr_code_base64 ?? null,
            'payment_method_id' => $request->has('subscription_payment') ? $createRequest['payment_method_id'] : $payment->payment_method_id,
            'payment_type_id'   => $request->has('subscription_payment') ? 'credit_card' : $payment->payment_type_id,
            'plan_id'           => $plan,
            'status_detail'     => $request->has('subscription_payment') ? 'pending_review_manual' : $payment->status_detail,
            'installments'      => $request->has('subscription_payment') ? 1 : $payment->installments,
            'status'            => $payment->status,
            'gross_amount'      => $request->has('subscription_payment') ? $payment->auto_recurring->transaction_amount : $payment->transaction_amount,
            'net_amount'        => roundDecimal($netAmount),
            'client_amount'     => $request->has('subscription_payment') ? $payment->auto_recurring->transaction_amount : $payment->transaction_details->total_paid_amount,
            'is_subscription'   => $request->has('subscription_payment'),
            'company_id'        => $company_id,
            'user_created'      => $request->user()->id
        ));

        // Pagamento foi criado. Validar a situação. Ele poder ter sido rejeitado diretamente.
        try {
            $this->mercado_pago_exception->setPayment($payment);
            $verify = $this->mercado_pago_exception->verifyTransaction($request->has('subscription_payment'));
        }  catch (Exception $exception) {
            return response()->json(['errors' => $exception->getMessage(), 'payment_id' => $payment->id], 400);
        }
        if ($verify['class'] == 'error') {
            return response()->json(['errors' => $verify['message'], 'payment_id' => $payment->id], 400);
        }

        $response = [
            'message' => $verify['message'],
            'payment_id' => $payment->id
        ];

        if ($request->has('subscription_payment')) {
            $response['payment_method'] = $createRequest['payment_method_id'];
            $response['init_point'] = $payment->init_point ?? '';
            $response['status'] = $payment->status;
        }

        return response()->json($response);
    }

    /**
     * @param   object      $payment
     * @throws  Exception
     */
    private function validatePaymentResult(object $payment): void
    {
        if ($payment->id === null) {
            $error_message = 'Unknown error cause';

            if (!property_exists($payment, 'id') || $payment->error !== null) {
                $sdk_error_message = $payment->error->message ?? null;
                $error_message = $sdk_error_message !== null ? $sdk_error_message : $error_message;
            }

            throw new Exception($error_message);
        }
    }

    public function fetchRequests(Request $request): JsonResponse
    {
        $result     = array();
        $draw       = $request->input('draw');
        $company_id = $request->user()->company_id;

        try {
            $filters        = array();
            $filter_default = array();
            $fields_order   = array('plans.name','plan_payments.payment_type_id','plan_payments.gross_amount','plan_payments.status', 'plan_payments.created_at', '');

            $filter_default[]['where']['plan_payments.company_id'] = $company_id;

            $query = array(
                'from' => 'plan_payments',
                'select' => array(
                    'plan_payments.id',
                    'plans.name',
                    'plan_payments.payment_method_id',
                    'plan_payments.payment_type_id',
                    'plan_payments.gross_amount',
                    'plan_payments.status',
                    'plan_payments.created_at'
                )
            );
            $query['join'][] = ['plans', 'plans.id', '=', 'plan_payments.plan_id'];

            $data = fetchDataTable(
                $query,
                array('plan_payments.id', 'desc'),
                null,
                ['PlanView'],
                $filters,
                $fields_order,
                $filter_default
            );

        } catch (Exception $exception) {
            return response()->json(getErrorDataTables($exception->getMessage(), $draw));
        }

        foreach ($data['data'] as $value) {
            $result[] = array(
                $value->name,
                getNamePaymentTypeMP($value),
                formatMoney($value->gross_amount, 2, 'R$ '),
                "<div class='badge badge-pill badge-lg badge-".getColorStatusMP($value->status)."'>" . __("mp.$value->status") . "</div>",
                formatDateInternational($value->created_at, DATETIME_BRAZIL),
                "<a href='".route('plan.view', ['payment_id' => $value->id])."' class='btn btn-primary btn-sm btn-rounded btn-action' data-bs-toggle='tooltip' title='Visualizar' ><i class='fas fa-eye'></i></a>"
            );
        }

        $output = array(
            "draw"              => $draw,
            "recordsTotal"      => $data['recordsTotal'],
            "recordsFiltered"   => $data['recordsFiltered'],
            "data"              => $result
        );

        return response()->json($output);
    }

    public function view(int $payment_id): View|Factory|RedirectResponse|Application
    {
        $company_id = Auth::user()->__get('company_id');
        $payment    = $this->plan_payment->getById($company_id, $payment_id);

        if (!$payment) {
            return redirect()->route('plan.request');
        }

        $user           = $this->user->getUserById($company_id);
        $company        = $this->company->getCompany($company_id);
        $plan_histories = $this->plan_history->getHistoryPayment($payment_id);

        return view('plan.view', compact('payment', 'company', 'user', 'plan_histories'));
    }

    private function makeDataToPay(int $plan, string $code_payment, Request $request): array
    {
        $company_id             = $request->user()->company_id;
        $check_payment_method   = $request->input('payment_method_id');
        $company_data           = $this->company->getCompany($company_id);
        $plan_data              = $this->plan->getById($plan);
        $card_client_name       = $request->input('card_client_name', $company_data->name) ?: $company_data->name;
        $first_company_name     = explode(' ', $card_client_name)[0];
        $last_company_name      = str_replace("$first_company_name ", '', $card_client_name);
        $system_name            = Normalizer::normalize(config('app.name'), Normalizer::NFD);
        $system_name            = preg_replace('/[\x{0300}-\x{036F}]/u', '', $system_name);
        $system_name            = str_replace(' ', '_', $system_name);
        $system_name            = strtoupper($system_name);
        $payer                  = $request->input('payer');

        if ($request->has('subscription_payment')) {
            if (!empty($plan_data->discount_subscription)) {
                $plan_data->value -= ($plan_data->value * ($plan_data->discount_subscription / 100));
            }
        }

        $plan_data->value = roundDecimal($plan_data->value, 2, false);

        $createRequest          = [
            'external_reference'    => $code_payment,
            "transaction_amount"    => roundDecimal($plan_data->value),
            "description"           => $plan_data->name,
            "payment_method_id"     => $request->input('payment_method_id'),
            'notification_url'      => str_Replace('http://localhost:8000', 'https://teste.locai.com.br', route('mercadopago.notification')),
            "statement_descriptor"  => $system_name,
            "additional_info"       => [
                "items"             => [
                    [
                        "id"            => $plan,
                        "title"         => $plan_data->name,
                        "description"   => $plan_data->name,
                        "category_id"   => "plan",
                        "quantity"      => 1,
                        "unit_price"    => $plan_data->value
                    ]
                ],
                "payer" => [
                    "first_name"    => $first_company_name,
                    "last_name"     => $last_company_name,
                    "phone" => [
                        "area_code" => extractDataPhone($company_data->phone_1)['ddd'],
                        "number"    => extractDataPhone($company_data->phone_1)['phone']
                    ],
                    "address" => [
                        "zip_code"      => $company_data->cep,
                        "street_name"   => "$company_data->address - $company_data->city/$company_data->state",
                        "street_number" => $company_data->number,
                    ]
                ]
            ]
        ];

        // É cartão
        if ($request->input('token') && $request->input('issuer_id')) {
            $check_payment_method = 'card';
        }

        switch ($check_payment_method) {
            case 'pix':
                $createRequest["payer"] = array(
                    "entity_type"    => "individual",
                    "type"           => "customer",
                    "email"          => $payer['email'],
                    "first_name"     => $first_company_name,
                    "last_name"      => $last_company_name,
                    "identification" => array(
                        "type"       => $company_data->type_person === 'pf' ? "CPF" : "CNPJ",
                        "number"     => onlyNumbers($company_data->cpf_cnpj)
                    ),
                    "address" => array(
                        "zip_code"      => $company_data->cep,
                        "street_name"   => $company_data->address,
                        "street_number" => $company_data->number,
                        "neighborhood"  => $company_data->neigh,
                        "city"          => $company_data->city,
                        "federal_unit"  => $company_data->state,
                    )
                );
                break;
            case 'bolbradesco':
            case 'pec':
                $createRequest["payer"] = array(
                    "entity_type"    => "individual",
                    "type"           => "customer",
                    "email"          => $payer['email'],
                    "first_name"     => $payer['first_name'],
                    "last_name"      => $payer['last_name'],
                    "identification" => array(
                        "type"       => $payer['identification']['type'],
                        "number"     => onlyNumbers($payer['identification']['number'])
                    ),
                    "address" => array(
                        "zip_code"      => $payer['address']['zip_code'],
                        "street_name"   => $payer['address']['street_name'],
                        "street_number" => $payer['address']['street_number'],
                        "neighborhood"  => $payer['address']['neighborhood'],
                        "city"          => $payer['address']['city'],
                        "federal_unit"  => $payer['address']['federal_unit'],
                    )
                );
                break;
            case 'card':
                $createRequest["token"]              = $request->input('token');
                $createRequest["installments"]       = $request->input('installments');
                $createRequest["issuer_id"]          = $request->input('issuer_id');
                $createRequest["payer"] = array(
                    "entity_type"    => "individual",
                    "type"           => "customer",
                    "email"          => $payer['email'],
                    "identification" => array(
                        "type"       => $payer['identification']['type'],
                        "number"     => onlyNumbers($payer['identification']['number'])
                    )
                );
                break;
            default:
                throw new Exception('Tipo de pagamento não encontrado.');
        }

        if ($request->has('subscription_payment')) {
            if (empty($plan_data->plan_id_gateway)) {
                throw new Exception('Código do plano no gateway de pagamento não encontrado.');
            }

            $datetime_start_date = new DateTime('now', new DateTimeZone(TIMEZONE_DEFAULT));
            $start_date = $datetime_start_date->format('Y-m-d\TH:i:s.') . sprintf('%03d', $datetime_start_date->format('v')) . 'Z';

            $datetime_end_date = new DateTime($start_date, new DateTimeZone(TIMEZONE_DEFAULT));
            $datetime_end_date->modify('+1 year');
            $end_date = $datetime_end_date->format('Y-m-d\TH:i:s.') . sprintf('%03d', $datetime_end_date->format('v')) . 'Z';

            $createRequest = array(
                'preapproval_plan_id'   => $plan_data->plan_id_gateway,
                'external_reference'    => $code_payment,
                'back_url'              => str_replace('http://localhost:8000/', 'https://app.locai.com.br/', $createRequest['notification_url']),  // URL de retorno após pagamento
                'reason'                => $createRequest['description'],  // Razão da assinatura (descrição do serviço ou produto)
                'payer_email'           => $createRequest['payer']['email'],
                'notification_url'      => route('mercadopago.notification'),
                "payment_method_id"     => $createRequest['payment_method_id'],
                'auto_recurring'        => [
                    'frequency'             => 1,  // Frequência mensal
                    'frequency_type'        => "months",  // Tipo mensal
                    'transaction_amount'    => roundDecimal($plan_data->value),  // Valor da mensalidade
                    'currency_id'           => "BRL",  // Moeda
                    'start_date'            => str_replace('+00:00', 'Z', $start_date),
                    'end_date'              => str_replace('+00:00', 'Z', $end_date),
                    'recurrent_payment'     => true,  // Definir como pagamento recorrente
                ],
                'card_token'    => $createRequest['token'],  // Token do cartão gerado pelo front-end
                'card_token_id' => $createRequest['token'],  // Token do cartão gerado pelo front-end
                'payer'         => [
                    'name'              => $createRequest['additional_info']['payer']['first_name'],  // Nome do cliente
                    'surname'           => $createRequest['additional_info']['payer']['last_name'],  // Sobrenome
                    'email'             => $createRequest['payer']['email'],  // E-mail do cliente
                    'identification'    => [
                        'type'      => $createRequest['payer']['identification']['type'],  // Tipo de documento
                        'number'    => $createRequest['payer']['identification']['number']  // Número do CPF do cliente
                    ],
                ],
                'additional_info' => [
                    'order_id'              => $createRequest['external_reference'],  // ID do pedido (pode ser útil para o controle interno)
                    'product_description'   => $createRequest['description'],  // Descrição do produto ou serviço
                ]
            );

            // Atualmente, autorizar o pagamento só funciona em produção.
            if (env('APP_ENV') === 'production') {
                $createRequest['status'] = 'authorized';
            }
        }

        return $createRequest;
    }

    public function cancelSubscription(Request $request): JsonResponse
    {
        $company_id = $request->user()->company_id;
        $plan_id = $request->input('plan_id');

        $payment = $this->plan_payment->getById($company_id, $plan_id);
        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Não foi possível localizar o plano!']);
        }

        try {
            MercadoPagoConfig::setAccessToken(env('MP_ACCESS_TOKEN'));

            // Instanciar o cliente de PreApproval
            $client = new PreApprovalClient();

            // Atualizar o status da assinatura para 'cancelled'
            $client->update($payment->id_transaction, ['status' => 'cancelled']);

            $this->plan_payment->edit(array('status' => 'cancelled'), $company_id, $plan_id);

            return response()->json(['success' => true, 'message' => 'Assinatura cancelada com sucesso!']);
        } catch (MPApiException $exception) {
            $error_message = $exception->getApiResponse()->getContent();

            Log::error("[MPApiException] Payment doesn't canceled to the company $company_id to the plan $plan_id.", [
                'request'   => $createRequest ?? [],
                'response'  => $error_message,
                'trace'     => $exception->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => $error_message['message']], 400);
        } catch (Exception $exception) {
            $error_message = $exception->getMessage();
            Log::error("[Exception] Payment doesn't canceled to the company $company_id to the plan $plan_id.", [
                'request'   => $createRequest ?? [],
                'response'  => $error_message,
                'trace'     => $exception->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => $error_message], 400);
        }
    }
}
