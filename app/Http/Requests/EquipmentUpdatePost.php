<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EquipmentUpdatePost extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return hasPermission(join('', array_slice(explode('\\', __CLASS__), -1)));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'type_equipment'    => ['required', Rule::in(['cacamba', 'others'])],
            'volume'            => ['required_if:type_equipment,cacamba', Rule::in(['Selecione ...',3,4,5,6,7,8,9,10])],
            'name'              => 'required_if:type_equipment,others',
            'reference'         => [
                'required',
                function ($attribute, $value, $fail) {
                    $exists = DB::table('equipments')
                                ->where(['reference' => $this->get('reference'), 'company_id' => $this->user()->company_id])
                                ->whereNotIn('id', [$this->get('equipment_id')])
                                ->count();

                    if ($exists) {
                        $fail('Referência do Equipamento está em uso!');
                    }
                }
            ]
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'type_equipment.*'     => 'Tipo de equipamento mal informado, tente novamente.',
            'volume.required_if'    => 'Selecione um volume.',
            'volume.in'             => 'Selecione um volume.',
            'name.required_if'      => 'Digite o nome do equipamento',
            'reference.required'    => 'Digite a referência do equipamento'
        ];
    }
}
