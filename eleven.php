<?php
class Eleven {
    private $XI_API_KEY;
    private $VOICE_ID;

    public function __construct($api_key, $voice_id) {
        $this->XI_API_KEY = $api_key;
        $this->VOICE_ID = $voice_id;
    }

    public function convertTextToSpeech($text, $output_filename) {
        // URL para o endpoint de texto para fala (TTS)
        $tts_url = "https://api.elevenlabs.io/v1/text-to-speech/{$this->VOICE_ID}/stream";

        // Cabeçalhos necessários para a requisição HTTP
        $headers = [
            "Accept: application/json",
            "xi-api-key: {$this->XI_API_KEY}",
            "Content-Type: application/json"
        ];

        // Dados a serem enviados na requisição (texto a ser convertido)
        $data = [
            "text" => $text,
            "model_id" => "eleven_multilingual_v2",
            "voice_settings" => [
                "stability" => 0.5,
                "similarity_boost" => 0.8,
                "style" => 0.0,
                "use_speaker_boost" => true
            ]
        ];

        // Realiza a requisição POST para o endpoint de TTS
        $ch = curl_init($tts_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Verifica se a requisição foi bem-sucedida
        if ($http_code == 200 && $response) {
            // Salva o áudio recebido em formato WAV
            if (file_put_contents($output_filename, $response) !== false) {
                // Converte o áudio para WAV usando mpg123
                $command_mpg = "mpg123 -w {$output_filename}.wav {$output_filename}";
                exec($command_mpg, $output_mpg, $return_var_mpg);

                if ($return_var_mpg === 0) {
                    // Converte o arquivo WAV para o formato desejado usando sox
                    $command_sox = "sox {$output_filename}.wav -r 8000 -c 1 {$output_filename}.tmp.wav";
                    exec($command_sox, $output_sox, $return_var_sox);

                    if ($return_var_sox === 0) {
                        // Converte o arquivo WAV para alaw
                        $alaw_filename = "{$output_filename}.alaw";
                        $command_alaw = "sox {$output_filename}.tmp.wav -t al -e a-law {$alaw_filename}";
                        exec($command_alaw, $output_alaw, $return_var_alaw);

                        // Remove arquivos intermediários
                        unlink("{$output_filename}.wav");
                        unlink("{$output_filename}.tmp.wav");

                        if ($return_var_alaw === 0) {
                            // Arquivo convertido para alaw com sucesso
                            return true;
                        } else {
                            // Erro ao converter para alaw
                            return false;
                        }
                    } else {
                        // Erro ao converter para formato desejado usando sox
                        unlink("{$output_filename}.wav");
                        return false;
                    }
                } else {
                    // Erro ao converter para WAV usando mpg123
                    return false;
                }
            } else {
                // Erro ao salvar o áudio
                return false;
            }
        } else {
            // Erro na requisição
            return false;
        }
    }
}
?>
