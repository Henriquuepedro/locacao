<?php

namespace App\Http\Controllers;

use App\Models\Config;
use App\Models\IntegrationToStore;
use App\Models\Integration;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    private Config $config;
    private IntegrationToStore $integration_to_store;
    private Integration $integration;

    public function __construct()
    {
        $this->config = new Config();
        $this->integration_to_store = new IntegrationToStore();
        $this->integration = new Integration();
    }

    public function updateConfig(Request $request)
    {
        if (!hasAdmin()) {
            return redirect()->route('dashboard');
        }

        $company_id = $request->user()->company_id;

        $dataConfigCompany   = $this->config->getConfigColumnAndValue($company_id);
        $configCompanyColumn = $dataConfigCompany['column'];
        $arrUpdate = [];

        foreach ($configCompanyColumn as $configIndex) {
            if (in_array($configIndex, ['id', 'company_id', 'user_update', 'created_at', 'updated_at'])) {
                continue;
            }

            $arrUpdate[$configIndex] = (bool)$request->input($configIndex);
        }

        if (is_null($dataConfigCompany['value'])) {
            $arrUpdate['company_id'] = $company_id;
            $updateConfig = $this->config->insert($arrUpdate);
        } else {
            $updateConfig = $this->config->edit($arrUpdate, $company_id);
        }

        if ($updateConfig) {
            return redirect()->route('config.index')
                ->with('success', "Configurações de empresa atualizada com sucesso!");
        }

        return redirect()->to(route('config.index').'#config')
            ->withErrors(['Não foi possível atualizar as configurações de empresa, tente novamente!'])
            ->withInput();
    }
}
