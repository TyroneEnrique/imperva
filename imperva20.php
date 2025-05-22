<?php

///echo (phpinfo());
set_time_limit(0);
date_default_timezone_set('America/Guayaquil');

global $numerotransacciones;
$numerotransacciones=50;

global $contador;
global $contadorwebup;

global $file;
global$fechaincial  ;
global $fechafinal ;

global $webhookUrl ;
global $num_cola;
global $file_path;
global $apiid;
global $apikey;
global $cuenta;
global $usuariowebhookimperva;
global $clavewebhook;
global $directorioBase;

$directorioBase = crearDirectorio();
// Función para crear el directorio 
function crearDirectorio()
{   
    global $directorioBase;
    $sistemaoperativo = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'Windows' : 'Linux';
    $directorioBase = ($sistemaoperativo === 'Windows') ? 'C:\\impervalogs' : '/opt/impervalogs';

    if (!file_exists( $directorioBase)) {
        if (!mkdir($directorioBase, 0777, true)) {
            $error = error_get_last();
            echo  " Error al crear el directorio '$directorioBase'. Error: " . $error['message'];
            registrarLogerrores("Error al crear el directorio. Por favor créelo manualmente: '$ruta'.");
            die();
        }else{
            crearArchivosLog();
        }
    } else{
        registrarLogregistros(".El directorio '$directorioBase' es existente.");
        crearArchivosLog();
    }
    return $directorioBase;
}

function crearArchivosLog() {
    global $directorioBase; // Asegura que usemos la ruta correcta

    $archivos = [
        'errores.log',
        'taskimperva.txt',
        'log_encola.txt'
    ];

    foreach ($archivos as $archivo) {
        foreach ($archivos as $archivo) {
            $rutaArchivo = $directorioBase . DIRECTORY_SEPARATOR . $archivo; 

            if (!file_exists($rutaArchivo)) {
                $handle = fopen($rutaArchivo, 'w');
                if ($handle) {
                    fclose($handle);
                    echo "Archivo '$rutaArchivo' creado exitosamente." . PHP_EOL;
                } else {
                    echo "Error: No se pudo crear el archivo '$rutaArchivo'." . PHP_EOL;
                }
            } else {
                //echo "El archivo '$rutaArchivo' ya existe." . PHP_EOL;
            }
        }
    }
}


//***variables de entorno***
//Varibles de entorno webhook

$filePath= str_replace(' ', '', 'C:\\impervalogs\\impervaproperties.txt');

if (!file_exists($filePath)) {
    crearDirectorio();
    registrarLogerrores("El archivo impervaproperties.txt no existe en la ruta C:\\eset\\impervaproperties.txt");
    registrarLogerrores("No se consulto evento, falta el archivo de properties coloquelo configure y vuelva a ejecutar la aplicación");
    die("El archivo impervaproperties.txt no existe en la ruta C:\\impervalogs\\impervaproperties.txt.\n");
}

$properties = parse_ini_file($filePath);
$webhookUrl = str_replace(' ', '', $properties['WEBHOOK_IMPERVA_URL']);
$clavewebhook = str_replace(' ', '', $properties['CLAVEWEBHOOK']);
$usuariowebhookimperva = str_replace(' ', '', $properties['USUARIOWEBHOOK']);

$impervaUrl = str_replace(' ', '', $properties['IMPERVA_API_BASE_URL']);
$apiid=str_replace(' ', '', $properties['APIID']);
$apikey=str_replace(' ', '', $properties['APIKEY']);
$cuenta = str_replace(' ', '', $properties['CAID']);



$contador = 0;
$contadorwebup = 0;

$file_path = realpath("C:\\impervalogs\\log_encola.txt");  // Usa realpath para verificar
$num_cola=contarLineasEnCola($file_path);

function contarLineasEnCola($file_path) {
    global $num_cola;
    global $file_path;
    // Verificar si el archivo existe
    if (!file_exists($file_path)) {
        registrarLogerrores("El archivo $file_path no existe en la ruta especificada");
        die("El archivo $file_path no existe en la ruta especificada.\n");
    }

    // Leer el archivo y contar las líneas
    $lineaencola = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $num_cola = count($lineaencola);
    return $num_cola;
}

function registrarLogerrores($mensaje)
{
    global $directorioBase;
    $logFile = $directorioBase . DIRECTORY_SEPARATOR . 'errores.log';
    $fecha = date('Y-m-d H:i:s');
    $mensajeCompleto = "[{$fecha}] {$mensaje}" . PHP_EOL;
    file_put_contents($logFile, $mensajeCompleto, FILE_APPEND);
}

function registrarLogregistros($mensaje)
{
    global $directorioBase;
    $logFile = $directorioBase . DIRECTORY_SEPARATOR . 'taskimperva.txt';
    $fecha = date('Y-m-d H:i:s');
    $mensajeCompleto = "[{$fecha}] {$mensaje}" . PHP_EOL;
    file_put_contents($logFile,$mensajeCompleto, FILE_APPEND);
}

// Función para registrar errores en el log
function registrarLogcola($mensaje)
{
    global $directorioBase;
    $logFile = $directorioBase . DIRECTORY_SEPARATOR . 'log_encola.txt';
    file_put_contents($logFile, $mensaje, FILE_APPEND);
}

function convertirFechaAMilisegundos($fecha) {
    // Configurar la zona horaria en UTC temporalmente
    $timestamp = strtotime($fecha);

    if ($timestamp === false) {
        registrarLogerrores("Formato de fecha inválido");
        return "Formato de fecha inválido";

    }

    // Convertir a milisegundos
    return $timestamp * 1000;
}

function convertirMilisegundosAFecha($milisegundos) {
    return date('Y-m-d\TH:i:s', $milisegundos / 1000);
}


$fechaincial='' ;
 $fechafinal='';

$fechanown = new DateTime();

$fechaincial = $fechanown->format('Y-m-d\TH:i:s');
$fechafinal = $fechanown->sub(new DateInterval('PT1H'))->format('Y-m-d\TH:i:s');

$fechaincial = "2025-05-19T00:00:00";
$fechafinal = "2025-05-21T23:00:00";

$starmilisegundo = convertirFechaAMilisegundos($fechaincial);
$endmilisegundo = convertirFechaAMilisegundos($fechafinal);

$urlDetections = "{$impervaUrl}?caid={$cuenta}&from_timestamp={$starmilisegundo}&to_timestamp={$endmilisegundo}";
$urlDetections = str_replace(' ', '', $urlDetections);

$ch = curl_init();
// Configuración de cURL
curl_setopt($ch, CURLOPT_URL,$urlDetections); // Establecer la URL
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Obtener la respuesta como string
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET"); // Método HTTP

$headersDetections = [
    "accept: application/json",
    "x-API-Id: ".$apiid,        // Reemplaza con tu API ID válido
    "x-API-Key: ".$apikey,    
];


// Función para obtener el token de acceso
// Función genérica para realizar solicitudes API
function realizarSolicitudApi($url, $postFields = null, $headers = [], $isPost = true)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($isPost && $postFields) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (curl_errno($ch)) {
        $errorMessage = "Error en cURL: " . curl_error($ch);
        registrarLogerrores("Error en cURL: " . curl_error($ch) . " " . PHP_EOL, FILE_APPEND);
        curl_close($ch);

        // Devolver un array con el error
        return [
            'success' => false,
            'error' => $errorMessage,
            'http_code' => $httpCode,
            'body' => null
        ];
    }
    curl_close($ch);

    // Devolver la respuesta exitosa
    return [
        'success' => true,
        'http_code' => $httpCode,
        'body' => $response
    ];
}


// Función para realizar solicitudes al webhook
function realizarSolicitudWebhook($url, $data, $headers)
{
 
    global $file_path;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    //curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);
    $responseBody = curl_exec($ch);
  
    if ($responseBody === false) {
        $error = curl_error($ch);
        $errorCode = curl_errno($ch);

        // Capturar error detallado
        $errorMessage = "Error cURL [{$errorCode}]: {$error} | URL: {$url}";
        /*registrarLogerrores("Registro en cola ".contarLineasEnCola($file_path)." - ".$errorMessage. PHP_EOL);
        echo $errorMessage. PHP_EOL, FILE_APPEND;
        echo "<br>";*/
        $contadorjson=contarLineasEnCola($file_path);
        $contadorjson= $contadorjson+1;
        registrarLogerrores("Registro en cola ".  $contadorjson." - ".$errorMessage. PHP_EOL);

    } else {
       /* Registrar respuesta HTTP y cuerpo
        $logMessage = "Respuesta HTTP: {$httpCode} | Cuerpo: {$responseBody}";
        registrarLogerrores($logMessage.contarLineasEnCola($file_path));
        echo $logMessage. PHP_EOL, FILE_APPEND;
        echo "<br>";
        */
    }


    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
          
        registrarLogerrores( "Webhook Error en cURL: " . curl_error($ch) . " ");
    }

    curl_close($ch);

    return [
        'http_code' => $httpCode,
        'body' => $responseBody
    ];
}
// Función para enviar datos al webhook
function enviarWebhook($data)
{


    global $contadorwebup;
    global $file_path;

    global $usuariowebhookimperva;
    global $clavewebhook;
    global $webhookUrl ;
    global $numerotransacciones;
   

    $headers = [
        "Content-Type: application/json",
        "Authorization: Basic " . base64_encode("$usuariowebhookimperva:$clavewebhook")
            
    ];
    //$response = realizarSolicitudWebhook($webhookUrl, $data, $headers);
    if( $contadorwebup>=$numerotransacciones)
    {
        registrarLogcola(json_encode($data). PHP_EOL, FILE_APPEND); 
    }

    else 
    {
        //$response = realizarSolicitudWebhook($webhookUrl, $data, $headers);
        $response['http_code']=200;
        if($response['http_code'] ===200)
        {

            $contadorwebup++;
            echo "<li style='color: green;'>✅ Enviado correctamente: Registro número ". $contadorwebup. json_encode($data) . "</li>";
            echo ('<br>');
        }
        else{
            registrarLogcola(json_encode($data). PHP_EOL, FILE_APPEND);
        }
    }
}



try 
{
    $responseDetections = realizarSolicitudApi($urlDetections, null, $headersDetections, false);
  
   
    if($responseDetections['http_code'] ==200)
    {
        $responseDataDetections = json_decode($responseDetections['body'], true);
        if (!empty($responseDataDetections)) {
            foreach ($responseDataDetections as $detection) {
                        if ( 1===1  
                           && $detection['severity'] !== 'MINOR' 
                            )  
                            {
                                $incidentUrl = trim($properties['IMPERVA_INCIDENT'], "'\"");

                                //obtner el detalles de los id
                                $urlip = str_replace('{incidentId}', $detection['id'], $incidentUrl);
                                
                                $responsearchip = realizarSolicitudApi($urlip, null, $headersDetections, false);
                                if ($responsearchip['http_code'] == 200 && $responseDetections['http_code']== 200 ) {
                                    $responsearchip = json_decode($responsearchip['body'], true);
                                    if (isset($responsearchip['attack_ips']) && is_array($responsearchip['attack_ips'])) {
                                        // Extraer todas las IPs usando array_column()
                                        $ips = array_column(array_column($responsearchip['attack_ips'], 'key'), 'ip');
                                    }
                                }
                                else{

                                   
                                
                                    $errorResponse = json_decode($responsearchip['body'], true); 
                                    // Verificar si la respuesta contiene "errMsg" (Caso 1)
                                    if (isset($errorResponse['errMsg']) && isset($errorResponse['errCode'])) {
                                        $mensajeError = "Error {$errorResponse['errCode']}: {$errorResponse['errMsg']}";
                                    }
                                    // Caso en que la estructura de la respuesta no sea reconocida
                                    else {
                                        $mensajeError = "Error desconocido: Revise la URL" . json_encode($errorResponse);
                                    }
                                    echo('Revise el Api  para obtener por id los incidente ,IMPERVA_INCIDENT'.$responsearchip['http_code'] . ' - ' . $mensajeError);
                                    
                                    registrarLogerrores("Revise el Api  para obtener por id los incidente,IMPERVA_INCIDENT en el archivo Properties ".$responseDetections['http_code'] . ' - ' . $mensajeError);
                                }
                                $ipsString = !empty($ips) ? implode(",", $ips) : "";
                           
                                if (!empty($ipsString))
                                {
                                    if ($detection['dominant_attack_ip']['ip'])
                                    {
                                        $ipsString= $detection['dominant_attack_ip']['ip'].','. $ipsString;

                                    }
                                }
                            

                                $contador++;
                                $milisegundos =  $detection['first_event_time'] ;// Ejemplo de milisegundos (correspondiente a 2025-01-14T23:59:00)
                                $fechafisrt= convertirMilisegundosAFecha($milisegundos);

                                $milisegundos =  $detection['last_event_time'] ;// Ejemplo de milisegundos (correspondiente a 2025-01-14T23:59:00)
                                $fechalast= convertirMilisegundosAFecha($milisegundos);

                                $formattedData = [
                                    "id" => $detection['id'],
                                    "main_sentence" => $detection['main_sentence'],
                                    "secondary_sentence" => $detection['secondary_sentence'],
                                    "events_count" => $detection['events_count'],
                                    "first_event_time" =>  $fechafisrt ?? '',
                                    "last_event_time" =>  $fechalast ?? '',
                                    "severity" => $detection['severity'] ?? '',
                                    "severity_explanation" => $detection['severity_explanation'] ?? '',
                                    "country" => $detection['dominant_attack_country']['country'] ?? '',
                                    "value" => $detection['dominant_attacked_host']['value'] ?? '',
                                    "name" => $detection['dominant_attack_tool']['name'] ?? '',
                                    "type" => $detection['dominant_attack_tool']['type'] ?? '',
                                    "dominant_attack_violation" => $detection['dominant_attack_violation']?? '',
                                    "incident_type" => $detection['incident_type']?? '',
                                    "ip"=>  $ipsString
                                ];

                                enviarWebhook($formattedData);

                            }            
                            else {
                            }
            }//for 

        
            $num_cola=contarLineasEnCola($file_path);
            registrarLogregistros("Imperva  se enviaron los datos entre la fecha de inicio ". $fechaincial." y la fecha de fin  ". $fechafinal." se encontraron ". $contador." registros, de los cuales   " . $contadorwebup." Recibidos en el Webhook ".$contadorwebup." fueron recibidos en el Webhook. Actualmente, hay  ".$num_cola.' incidentes en cola.'. PHP_EOL, FILE_APPEND);
           
            echo ("Imperva Fecha inicio ". $fechaincial." Fecha fin ". $fechafinal." Registros que cumplen con las condiciones  detectados " . $contador . "  Recibidos en el Webhook " . $contadorwebup." Registro en cola ".$num_cola. PHP_EOL);
        } 
        else 
        {
            $num_cola=contarLineasEnCola($file_path);
            echo "Imperva no se encontraron detecciones Fecha inicio ". $fechaincial." Fecha fin ". $fechafinal," registros en cola ".$num_cola." ";
            registrarLogregistros("Imperva Fecha inicio ". $fechaincial." Fecha fin ". $fechafinal." registro encontrados". $contador." Recibidos en el Webhook ".$contadorwebup."Registro en cola ".$num_cola. PHP_EOL, FILE_APPEND);
        }

    }
    else
    {
        $errorResponse = json_decode($responseDetections['body'], true); 
        // Verificar si la respuesta contiene "errMsg" (Caso 1)
        if (isset($errorResponse['errMsg']) && isset($errorResponse['errCode'])) {
            $mensajeError = "Error {$errorResponse['errCode']}: {$errorResponse['errMsg']}";
        }
        // Caso en que la estructura de la respuesta no sea reconocida
        else {
            $mensajeError = "Error desconocido: Revise la URL" . json_encode($errorResponse);
        }
      echo('Revise las credenciales del Api Imperva,APIID,APIKEY,CAID en el archivo Properies '.$responseDetections['http_code'] . ' - ' . $mensajeError);
        
      registrarLogerrores("Revise las credenciales del Api Imperva,APIID,APIKEY,CAID en el archivo Properties ".$responseDetections['http_code'] . ' - ' . $mensajeError);
    }

}
catch (Exception $e) {
    // Manejar la excepción
    registrarLogerrores(" Error: " . $e->getMessage());
    
    echo "Error: " . $e->getMessage();
    die(); // Detener la ejecución del script
}




        
    


