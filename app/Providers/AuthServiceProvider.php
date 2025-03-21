<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define('admin', function (User $user) {
            return $user->type_user == 1;
        });

        Gate::define('admin-master', function (User $user) {
            return $user->type_user == 2;
        });

        ResetPassword::toMailUsing(function ($notifiable, $url) {
            $rest_url = url(route('password.reset', [
                'token' => $url,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->greeting('Olá,')
                ->subject('Redefinição de senha')
                ->line('Você está recebendo este e-mail porque recebemos uma solicitação de redefinição de senha para sua conta.')
                ->action('Redefinir senha', $rest_url)
                ->line('Este link de redefinição de senha irá expirar em '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' minutos.')
                ->line('Se você não solicitou uma redefinição de senha, nenhuma ação adicional será necessária.');
        });
    }
}
