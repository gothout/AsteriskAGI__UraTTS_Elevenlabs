#!/usr/bin/php
<?php
// Definir o caminho do arquivo de log
//$log_file = '/var/log/asterisk/agi_test.log';

// Função para escrever mensagens de log com data e hora
// function write_log($message) {
//     global $log_file;
//     $timestamp = date('Y-m-d H:i:s');
//     $log_message = "[$timestamp] $message\n";
//     file_put_contents($log_file, $log_message, FILE_APPEND);
// }
// Função para converter números em palavras
function numberToWords($number) {
    // Array com as palavras correspondentes aos dígitos
    $words = [
        0 => 'zero', 1 => 'um', 2 => 'dois', 3 => 'três', 4 => 'quatro',
        5 => 'cinco', 6 => 'seis', 7 => 'sete', 8 => 'oito', 9 => 'nove'
    ];

    // Converte o número em string para iterar pelos dígitos
    $digits = str_split((string)$number);
    $result = [];

    foreach ($digits as $digit) {
        if (isset($words[$digit])) {
            $result[] = $words[$digit];
        }
    }

    return implode(' ', $result);
}
// Início do script AGI
//write_log("AGI Invocada");

// Incluir a classe AGI e outras dependências
require_once ('phpagi.php');
require_once ('eleven.php');
//write_log("Carregou phpagi.php e eleven.php");

// Instanciar o objeto AGI
$agi = new AGI();

// Obter o Caller ID da requisição AGI
$callerid = $agi->request['agi_callerid'];
//write_log("CallerID carregado: $callerid");

// Diretório de trabalho para os arquivos de áudio
$work_dir = "/var/lib/asterisk/sounds";
//write_log("[$callerid] Diretório para arquivos de áudio: $work_dir");

// Instanciar o objeto Eleven para operações de áudio   ////no segundo campo vou precisar do codigo de voz
$eleven = new Eleven("API_KEY", "Xb7hH8MSUJpSbSDYk0k2");

// Texto a ser convertido em áudio (você pode receber o nome do arquivo como parâmetro)
$texto = "O ramal que está ligando é " . numberToWords($callerid);
//write_log("[$callerid] Áudio automático gerado: $texto");

// Gerar um nome de arquivo aleatório único
do {
    $random_code = md5(uniqid(mt_rand(), true));
    $arquivo_audio = "{$random_code}.wav";
    $output_file = "{$work_dir}/{$arquivo_audio}";
} while (file_exists($output_file));

//write_log("[$callerid] Nome do arquivo de áudio gerado: $arquivo_audio");

// Converter texto em fala usando Eleven
$conversion_result = $eleven->convertTextToSpeech($texto, $output_file);

if ($conversion_result) {
    // Verifica se o arquivo de áudio foi gerado
    if (file_exists($output_file)) {
        //write_log("[$callerid] Áudio gerado com sucesso: $output_file");

        // Tocar o áudio gerado para o caller
        $agi->exec("Playback", $arquivo_audio);
        //write_log("[$callerid] Reproduzindo áudio: $arquivo_audio");

        // Remover arquivos temporários
        unlink($output_file); // Remove o arquivo .wav gerado
        unlink("{$output_file}.alaw"); // Remove o arquivo .wav.alaw gerado

        //write_log("[$callerid] Arquivos temporários removidos");

    } else {
        //write_log("[$callerid] Falha ao gerar áudio em $output_file.");
        $agi->hangup();
    }
} else {
    //write_log("[$callerid] Falha ao converter texto em áudio.");
    $agi->hangup();
}

// Finalizar a chamada
$agi->hangup();
//write_log("[$callerid] AGI Script Completed");
?>
