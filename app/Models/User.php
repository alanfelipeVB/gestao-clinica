<?php

namespace App\Models;

use App\Enums\PerfilUsuario;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'nome', 'email', 'telefone', 'instagram', 'profissao', 'perfil', 'ativo', 'password',
    'foto', 'bio', 'exibir_no_site', 'publicar_whatsapp',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'perfil' => PerfilUsuario::class,
            'ativo' => 'boolean',
            'exibir_no_site' => 'boolean',
            'publicar_whatsapp' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Agendamentos em que o usuário é o profissional responsável.
     *
     * @return HasMany<Agendamento, $this>
     */
    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class, 'user_id');
    }

    /**
     * Primeiro nome, ignorando títulos (ex.: "Dra. Ana Ribeiro" → "Ana").
     */
    public function primeiroNome(): string
    {
        $partes = preg_split('/\s+/', trim($this->nome));
        $titulos = ['dr', 'dra', 'sr', 'sra', 'prof', 'profa'];

        while (count($partes) > 1 && in_array(mb_strtolower(rtrim($partes[0], '.')), $titulos, true)) {
            array_shift($partes);
        }

        return $partes[0];
    }

    /**
     * Iniciais para o avatar sem foto (ex.: "Dra. Ana Ribeiro" → "AR").
     */
    public function iniciais(): string
    {
        $partes = preg_split('/\s+/', trim($this->nome));
        $ultimo = count($partes) > 1 ? mb_substr(end($partes), 0, 1) : '';

        return mb_strtoupper(mb_substr($this->primeiroNome(), 0, 1).$ultimo);
    }

    /**
     * URL da foto (com versão para invalidar o cache) ou null.
     */
    public function urlFoto(): ?string
    {
        return $this->foto ? route('profissionais.foto', ['user' => $this, 'v' => substr(md5($this->foto), 0, 8)]) : null;
    }

    /**
     * O WhatsApp só é publicado com autorização do próprio profissional.
     */
    public function whatsappPublico(): ?string
    {
        return $this->publicar_whatsapp ? $this->telefone : null;
    }

    public function isAdmin(): bool
    {
        return $this->perfil === PerfilUsuario::Admin;
    }

    public function isProfissional(): bool
    {
        return $this->perfil === PerfilUsuario::Profissional;
    }

    #[Scope]
    protected function ativos(Builder $query): void
    {
        $query->where('ativo', true);
    }

    #[Scope]
    protected function profissionais(Builder $query): void
    {
        $query->where('perfil', PerfilUsuario::Profissional);
    }

    /**
     * Profissionais exibidos na página inicial pública.
     */
    #[Scope]
    protected function naPaginaInicial(Builder $query): void
    {
        $query->where('ativo', true)
            ->where('perfil', PerfilUsuario::Profissional)
            ->where('exibir_no_site', true);
    }

    public function apareceNaPaginaInicial(): bool
    {
        return $this->ativo && $this->isProfissional() && $this->exibir_no_site;
    }

    /**
     * Busca por nome ou e-mail.
     */
    #[Scope]
    protected function busca(Builder $query, ?string $termo): void
    {
        $query->when($termo, fn (Builder $q) => $q->where(function (Builder $q) use ($termo) {
            $q->where('nome', 'like', "%{$termo}%")
                ->orWhere('email', 'like', "%{$termo}%");
        }));
    }
}
