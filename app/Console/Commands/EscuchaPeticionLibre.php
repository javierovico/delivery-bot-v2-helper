<?php

namespace App\Console\Commands;

use App\Models\DeliveryPrincipal\Bot;
use App\Models\LogTelegram;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class EscuchaPeticionLibre extends Command
{
    const BASE_URL = 'https://api.telegram.org/bot';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'escucha-peticion-libre {codigo}';

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

    private function urlMethod(string $method, ?Bot $bot): string
    {
//        $token = config('telegrambot.api_key');
        $token = $bot->token_api;
        return self::BASE_URL . $token . '/' . $method;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $codigo = $this->argument('codigo');
        $bot = Bot::getByCode($codigo, true);
        $this->info($codigo);
        $output = new ConsoleOutput();
        $cantidadPeticion = 0;
        $url = $this->urlMethod("getUpdates", $bot);
        $this->info("URL: " . $url);
        /** @var LogTelegram $ultimoLog */
        $ultimoLog = LogTelegram::query()->orderByDesc(LogTelegram::COLUMNA_UPDATE_ID)->first();
        $ultimoId = $ultimoLog ? $ultimoLog->update_id : null;
        $barCargaContactos = new ProgressBar($output->section());
        $barCargaContactos->setFormat("UltimoId: <info>%ultimoId%</info>\nCantidad Peticiones: <info>%cantidadPeticion%</info>\nRegistros nuevos: <info>%current%</info>\nUltimo Registro: <info>%ultimoRegistro%</info>");
        $barCargaContactos->setMessage($ultimoId ?: 'null', 'ultimoId');
        $barCargaContactos->setMessage($cantidadPeticion, 'cantidadPeticion');
        $barCargaContactos->setMessage('--', 'ultimoRegistro');
        $barCargaContactos->start();
        $barCargaContactos->display();
        $fechaUltimoRegistro = null;
        do {
            $request = Http::withHeaders(['Accept' => 'application/json'])->post($url, [
                'offset' => $ultimoId + 1,
                'limit'  => 100,
                'timeout' => 60,
            ]);
            $dataRequest = $request->json();
            if (!$dataRequest['ok']) {
                $this->error($request->body());
                exit;
            }
            $barCargaContactos->setMessage(++$cantidadPeticion, 'cantidadPeticion');
            $barCargaContactos->display();
            foreach ($dataRequest['result'] as $input) {
                $urlInterna = config('helper.urlInterna') . '/' . $bot->codigo;
//                $secretToken = config('helper.secret_token');
                $secretToken = $bot->token_bot;
                $request = Http::withHeaders(['X-Telegram-Bot-Api-Secret-Token' => $secretToken])->post($urlInterna ."?XDEBUG_SESSION_START=PHPSTORM", $input);
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
                $ultimoId = $input['update_id'];
                LogTelegram::addLog($bot->id, $input,$output, LogTelegram::TIPO_RESEND, null, $ultimoId)->id;
                $barCargaContactos->setMessage($ultimoId ?: 'null', 'ultimoId');
                $barCargaContactos->advance();
            }
            if (count($dataRequest['result'])) {
                $fechaUltimoRegistro = CarbonImmutable::now();
            }
            if ($fechaUltimoRegistro) {
                $barCargaContactos->setMessage(CarbonImmutable::now()->diffInMinutes($fechaUltimoRegistro) . 's', 'ultimoRegistro');
            }
            $barCargaContactos->display();
        } while (true);
    }
}
