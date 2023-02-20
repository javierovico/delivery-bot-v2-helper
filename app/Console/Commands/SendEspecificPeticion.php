<?php

namespace App\Console\Commands;

use App\Models\LogTelegram;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SendEspecificPeticion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send-especific-peticion {peticionId}';

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

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $peticionId = $this->argument('peticionId');
        $registro = LogTelegram::findOrFail($peticionId);
        $input = $registro->input;
        $urlInterna = config('helper.urlInterna');
        $secretToken = config('helper.secret_token');
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
        $this->info("Enviando peticion " . $peticionId);
        $this->info(json_encode($output, JSON_PRETTY_PRINT));
        return 0;
    }
}
