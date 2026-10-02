<?php

namespace Tests\Feature;

use App\Models\Agendamento;
use App\Models\User;
use App\Services\AgendamentoService;
use Database\Seeders\DemonstracaoSeeder;
use Database\Seeders\SalaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DemonstracaoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_gera_dados_sem_conflitos_e_e_idempotente(): void
    {
        $this->travelTo(Carbon::parse('2026-10-07 10:00:00'));

        $this->seed(SalaSeeder::class);
        $this->seed(DemonstracaoSeeder::class);
        $total = Agendamento::count();
        $this->seed(DemonstracaoSeeder::class);

        $this->assertSame(5, User::where('email', 'like', '%@demo.test')->count());
        $this->assertGreaterThan(50, $total);
        $this->assertSame($total, Agendamento::count());

        // Nenhum agendamento se sobrepõe a outro na mesma sala ou do mesmo profissional.
        $servico = app(AgendamentoService::class);
        foreach (Agendamento::all() as $a) {
            $this->assertNull($servico->buscarConflito('sala_id', $a->sala_id, $a->inicio, $a->fim, $a->id));
            $this->assertNull($servico->buscarConflito('user_id', $a->user_id, $a->inicio, $a->fim, $a->id));
        }
    }
}
