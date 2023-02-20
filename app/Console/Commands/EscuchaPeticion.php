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
                        $rutaProyecto = 'delivery-bot-v2';
                        $error = $request->json('error');
                        $this->error($error);
                        $this->table(['archivo','function','class','linea'],collect($request->json('trace'))
                            ->filter(fn($i)=>!key_exists('file',$i) || !preg_match('/\/'.$rutaProyecto.'\/vendor\//', $i['file']))
                            ->map(fn($i) => [
                                key_exists('file',$i)?preg_replace('/.*' . $rutaProyecto . '\//', '', $i['file']): 'n',
                                key_exists('function',$i)?$i['function']:'sf',
                                key_exists('class',$i)?$i['class']:'sc',
                                key_exists('line',$i)?$i['line']:'sl'
                            ])
                        );
                        throw new \RuntimeException("Fail");
                    } else {
                        throw new \RuntimeException($request->body());
                    }
                }
                $output = $request->json();
                $ultimoId = $data['id'];
                LogTelegram::addLog(0, $input,$output, LogTelegram::TIPO_RESEND, $ultimoId)->id;
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
