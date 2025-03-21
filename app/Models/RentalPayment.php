<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RentalPayment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'rental_id',
        'parcel',
        'due_day',
        'due_date',
        'due_value',
        'payment_id',
        'payment_name',
        'payday',
        'user_insert',
        'user_update'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];

    public function insert(array $data)
    {
        return $this->create($data);
    }

    public function inserts(array $datas)
    {
        foreach ($datas as $data)
            if (!$this->create($data)) return false;

        return true;
    }

    public function remove($rental_id, $company_id)
    {
        return $this->getPayments($company_id, $rental_id)->each(fn ($register) => $register->delete());
    }

    public function removeByPaid($company_id, $rental_id)
    {
        return $this->where(['rental_id' => $rental_id, 'company_id' => $company_id, 'payment_id' => null])->get()->each(fn ($register) => $register->delete());
    }

    public function updateById(array $data, int $id)
    {
        return $this->where('id', $id)->first()->fill($data)->save();
    }

    public function getPayments($company_id, $rental_id)
    {
        return $this->where(['rental_id' => $rental_id, 'company_id' => $company_id])->orderBy('due_date', 'ASC')->get();
    }

    public function getPayment($company_id, $payment_id)
    {
        if (is_numeric($payment_id)) {
            return $this->where(['id' => $payment_id, 'company_id' => $company_id])->first();
        } elseif (is_array($payment_id)) {
            return $this->whereIn('id', $payment_id)->where('company_id', $company_id)->get();
        }
        return [];
    }

    public function getPaymentByRentalAndDate($company_id, $rental_id, string $due_date)
    {
        return $this->where(['rental_id' => $rental_id, 'company_id' => $company_id, 'due_date' => $due_date])->first();
    }

    public function getCountTypePayments(int $company_id, int $client, string $start_date, string $end_date): array
    {
        $data = array();

        foreach (array(
//             'late' => array(
//                 ['rental_payments.due_date', '<', date(DATE_INTERNATIONAL)],
//                 ['rental_payments.payday', '=', NULL]
//             ),
             'without_pay' => array(
                 //['rental_payments.due_date', '>=', date(DATE_INTERNATIONAL)],
                 ['rental_payments.payday', '=', NULL]
             ),
             'paid' => array(
                 ['rental_payments.payday', '<>', NULL]
             )
        ) as $type => $where) {
            $where = array_merge(array(['rentals.company_id', '=', $company_id]), $where);

            if ($client) {
                $where = array_merge(array(['rentals.client_id', '=', $client]), $where);
            }

            $data[$type] = $this
                ->join('rentals','rental_payments.rental_id','=','rentals.id')
                ->whereBetween('rental_payments.due_date', [$start_date, $end_date])
                ->where($where)
                ->get()
                ->count();
        }

        return $data;
    }

    public function getRentals(int $company_id, array $filters, int $init = null, int $length = null, string $search_client = null, array $order_by = array(), string $type_rental = null, bool $return_count = false)
    {
        $rental = $this ->select(
            'rentals.id',
            'rentals.code',
            'clients.name as client_name',
            'rentals.address_name',
            'rentals.address_number',
            'rentals.address_zipcode',
            'rentals.address_complement',
            'rentals.address_neigh',
            'rentals.address_city',
            'rentals.address_state',
            'rentals.created_at',
            'rental_payments.due_date',
            'rental_payments.due_value',
            'rental_payments.id as rental_payment_id',
            'rental_payments.payment_id',
            'rental_payments.payday',
        )->join('rentals','rental_payments.rental_id','=','rentals.id')
        ->join('clients','clients.id','=','rentals.client_id')
        ->where('rentals.company_id', $company_id);

        if ($search_client) {
            $rental->where(function ($query) use ($search_client) {
                $query->where('rentals.code', 'like', "%".(int)onlyNumbers($search_client)."%")
                    ->orWhere('clients.name', 'like', "%$search_client%")
                    ->orWhere('rentals.address_name', 'like', "%$search_client%")
                    ->orWhere('rental_payments.due_date', 'like', "%$search_client%");
            });
        }

        if ($type_rental) {
            switch ($type_rental) {
//                 case 'late':
//                    $rental->where(array(
//                        ['rental_payments.due_date', '<', date(DATE_INTERNATIONAL)],
//                        ['rental_payments.payday', '=', NULL]
//                    ));
//                    break;
                 case 'without_pay':
                     $rental->where(array(
                        //['rental_payments.due_date', '>=', date(DATE_INTERNATIONAL)],
                        ['rental_payments.payday', '=', NULL]
                    ));
                    break;
                 case 'paid':
                    $rental->where(array(
                        ['rental_payments.payday', '<>', NULL]
                    ));
                    break;
            }
        }

        if ($filters['client'] !== null) {
            $rental->where('rentals.client_id', $filters['client']);
        }

        if ($filters['end_date'] !== null && $filters['start_date'] !== null) {
            $rental->whereBetween('rental_payments.due_date', [$filters['start_date'], $filters['end_date']]);
        }

        if (count($order_by) !== 0) {
            $rental->orderBy($order_by['field'], $order_by['order']);
        } else {
            $rental->orderBy('rentals.code', 'asc');
        }

        if ($init !== null && $length !== null) {
            $rental->offset($init)->limit($length);
        }

        if ($return_count) {
            return $rental->get()->count();
        }

        return $rental->get();
    }

    public function getBillsToReportWithFilters(int $company_id, array $filters, bool $synthetic = true, array $order_by = array())
    {
        $rental = $this ->select(
            'rentals.id',
            'rentals.code',
            'clients.name as client_name',
            'form_payments.name as payment_name',
            'rental_payments.parcel',
            'rental_payments.due_date',
            'rental_payments.payday',
            'rental_payments.due_value',
            'rental_payments.payment_id'
        )
        ->join('rentals','rental_payments.rental_id','=','rentals.id')
        ->join('clients','clients.id','=','rentals.client_id')
        ->leftJoin('form_payments','form_payments.id','=','rental_payments.payment_id')
        ->where('rentals.company_id', $company_id);

        // Filtrar registros por data.
        switch ($filters['_date_filter']) {
            case 'created':
            default:
                $date_filter = 'rentals.created_at';
                break;
            case 'due':
                $date_filter = 'rental_payments.due_date';
                break;
            case 'pay':
                $date_filter = 'rental_payments.payday';
                break;
        }

        $rental->whereBetween($date_filter, ["{$filters['_date_start']} 00:00:00", "{$filters['_date_end']} 23:59:59"]);

        // Faz os filtros conforme o que foi informado.
        foreach ($filters as $filter_key => $filter_value) {
            // chave que comecem com "_", devem se ignoradas.
            if (substr($filter_key, 0, 1) === '_') {
                continue;
            }

            $rental->where($filter_key, $filter_value[0], $filter_value[1]);
        }

        // Ordena os registros.
        if (!empty($order_by)) {
            $rental->orderBy($order_by[0], $order_by[1]);
        } else {
            $rental->orderBy('rentals.code', 'DESC');
        }

        // Agrupa os registros por locação.
        if ($synthetic) {
            $rental->groupBy('rentals.id');
        }

        return $rental->get();
    }

    public function getPaymentByRentalAndDueDateAndValue(int $company_id, int $rental_id, string $due_date, float $due_value)
    {
        return $this->where(array(
            'company_id'    => $company_id,
            'rental_id'     => $rental_id,
            'due_date'      => $due_date,
            'due_value'     => $due_value
        ))->first();
    }

    public function getPaymentsPaidByRental(int $company_id, int $rental_id)
    {
        return $this->where(array(
            'company_id'    => $company_id,
            'rental_id'     => $rental_id
        ))->where('payment_id', '!=', null)->get();
    }

    public function getBillsForMonth($company_id, $year, $month): float|int
    {
        $register = $this->select(DB::raw('SUM(due_value) as total'))
            ->where([
                ['payment_id', '<>', null],
                ['company_id', '=', $company_id]
            ])
            ->whereYear('payday', $year)
            ->whereMonth('payday', $month)
            ->first();

        if ($register) {
            if ($register->total) {
                return roundDecimal($register->total);
            }
        }

        return 0;
    }

    public function getBillsForDate(int $company_id, string $date): float|int
    {
        $register = $this->select(DB::raw('SUM(due_value) as total'))
            ->where([
                ['payment_id', '<>', null],
                ['company_id', '=', $company_id]
            ])
            ->whereDate('payday', $date)
            ->first();

        if ($register) {
            if ($register->total) {
                return roundDecimal($register->total);
            }
        }

        return 0;
    }

    public function getBillClientByDate(int $company_id, string $date)
    {
        return $this->select(DB::raw('SUM(rental_payments.due_value) as total, rentals.client_id, clients.name, count(rentals.id) as total_payment_client'))
            ->join('rentals', 'rentals.id', '=', 'rental_payments.rental_id')
            ->join('clients', 'rentals.client_id', '=', 'clients.id')
            ->where([
                ['rental_payments.payment_id', '=', null],
                ['rentals.company_id', '=', $company_id]
            ])
            ->whereDate('rental_payments.due_date', $date)
            ->groupBy('rentals.client_id')
            ->get();
    }

    public function getBillLate(int $company_id)
    {
        $date = dateNowInternational(null, DATE_INTERNATIONAL);

        return $this->select(DB::raw('SUM(due_value) as total_value, count(id) as total_count'))
            ->where(array(
                ['company_id', '=', $company_id],
                ['payment_id', '=', null],
                ['due_date', '<', $date]
            ))->first();
    }
}
