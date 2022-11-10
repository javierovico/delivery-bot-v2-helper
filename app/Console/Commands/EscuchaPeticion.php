<?php

namespace App\Console\Commands;

use App\Models\LogTelegram;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class EscuchaPeticion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'escucha-peticion {--automatico}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $automatico = $this->option('automatico');
        $output = new ConsoleOutput();

        /** @var LogTelegram $ultimoLog */
        $ultimoLog = LogTelegram::query()->orderByDesc(LogTelegram::COLUMNA_EXTERNAL_ID)->first();
        $ultimoId = $ultimoLog ? $ultimoLog->external_id : null;
        if ($automatico) {
            $cantidadPeticion = 0;
            $barCargaContactos = new ProgressBar($output->section());
            $barCargaContactos->setFormat("UltimoId: <info>%ultimoId%</info>\nCantidad Peticiones: <info>%cantidadPeticion%</info>\nRegistros nuevos: <info>%current%</info>");
            $barCargaContactos->setMessage($ultimoId ?: 'null', 'ultimoId');
            $barCargaContactos->setMessage($cantidadPeticion, 'cantidadPeticion');
            $barCargaContactos->start();
        } else {
            $this->info("Ultimo ID: " . $ultimoId);
        }
        do {
            $datas = Http::withHeaders(['Accept' => 'application/json'])->get("https://delivery-bot-v2.javierovico.com/api/logs",[
                'tipo' => 'logOnly',
                'orderDirection' => 'asc',
                'lastId' => $ultimoId
            ])->json('data');
            if ($automatico) {
                $barCargaContactos->setMessage(++$cantidadPeticion, 'cantidadPeticion');
                $barCargaContactos->display();
            }
            foreach ($datas as $data) {
                $input = $data['input'];
                $request = Http::post("http://delivery-bot-v2.javierovicolocal.com/api/telegram/webhook?XDEBUG_SESSION_START=PHPSTORM", $input);
                if (!$request->successful() || !$request->json()) {
                    if ($request->json()) {
                        $error = $request->json('error');
                        $this->error($error);
                        $this->table(['archivo','linea'],collect($request->json('trace'))
                            ->filter(fn($i)=>!preg_match('/\/delivery-bot-v2\/vendor\//', $i['file']))
                            ->map(fn($i) => [$i['file'],$i['line']])
                        );
                        throw new \RuntimeException("Fail");
                    } else {
                        throw new \RuntimeException($request->body());
                    }
                }
                $output = $request->json();
                $ultimoId = $data['id'];
                LogTelegram::addLog($input,$output, LogTelegram::TIPO_RESEND, $ultimoId)->id;
                if ($automatico) {
                    $barCargaContactos->setMessage($ultimoId ?: 'null', 'ultimoId');
                    $barCargaContactos->advance();
                    $barCargaContactos->display();
                } else {
                    $this->info("ID: " . $ultimoId);
//                    $this->info("INPUT:");
//                    $this->info(json_encode($input,JSON_PRETTY_PRINT));
                }
            }
            if ($automatico) {
                sleep(5);
            }
        } while ($automatico || $this->confirm("Leer nuevamente", true));
    }
}
