<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetResidue extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'budget_id',
        'residue_id',
        'name_residue',
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

    public function remove($budget_id, $company_id)
    {
        return $this->getResidues($company_id, $budget_id)->each(fn ($register) => $register->delete());
    }

    public function getResidues($company_id, $budget_id)
    {
        return $this->where(['budget_id' => $budget_id, 'company_id' => $company_id])->get();
    }
}
