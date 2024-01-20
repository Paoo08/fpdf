<?php
include "conexion.php";
session_start();

$usuario = $_SESSION['id'];
$sqlProductos = "SELECT * FROM carrito_usuarios INNER JOIN productos ON productos.id = id_producto WHERE id_usuario = $usuario";
$resultado = mysqli_query($con, $sqlProductos);

if ($resultado) {
    $productos = mysqli_fetch_all($resultado, MYSQLI_ASSOC);
} else {
    $productos = array(); 
}

use Dompdf\Dompdf;
use Dompdf\Options;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require '/Applications/XAMPP/xamppfiles/htdocs/Conexion/vendor/autoload.php';

$mail = new PHPMailer(true);
$correo = $_SESSION['correo'];

$dompdf = new Dompdf();
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
$dompdf = new Dompdf($options);

try {
    $mail->SMTPDebug = 0;									 
    $mail->isSMTP();										 
    $mail->Host	 = 'smtp.gmail.com';				 
    $mail->SMTPAuth = true;							 
    $mail->Username = 'paola.permz@gmail.com';				 
    $mail->Password = 'gomh deze ftby kpun';

    $mail->SMTPSecure = 'tls';	
    $mail->Port	 = 587; 

    $mail->setFrom('paola.permz@gmail.com', 'Kalista');		 
    $mail->addAddress($correo, 'usuarios');
    $mail->isHTML(true);								 
    $mail->Subject = 'ARREGLO PDF';
    
    // Configura la zona horaria a México Centro
    date_default_timezone_set('America/Mexico_City');

    // Obtiene la fecha y hora actual en el formato deseado
    $fecha = date('Y/m/d');
    $hora = date('h:i A');

    $html = '
    <html>
    <head>
        <style>
            /* Estilo para el encabezado */
            header {
                background-color: #C1719A; /* Color rosa para el encabezado */
                padding: 10px;
                text-align: center;
                border-radius: 10px;
            }
            /* Estilo para el título "KALISTA" en rojo */
            h1 {
                color: #8E0505;
                margin: 0;
            }
            /* Estilo para la tabla */
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 30px;
                color: white;
                background-color: #a3a8b7;
                border-radius: 8px;
            }
            th, td {
                border: 1px solid #643843;
                padding: 10px;
                text-align: left;
            }
            /* Estilo para las imágenes */
            img {
                max-width: 100px; /* Ajusta el tamaño de la imagen según sea necesario */
                max-height: 100px;
            }
            .arreglo-celda {
                color: #643843; /* Cambiado a #643843 */
                font-weight: bolder;
            }
            /* Estilo para el total */
            .total-row td {
                font-weight: bold;
            }
            /* Fondo gris oscuro para la columna de descripción, precio, cantidad y total */
            td:nth-child(1), td:nth-child(2), td:nth-child(3), td:nth-child(4) {
                background-color: #E8E8E8;
                color: black;
            }
            /* Fondo blanco para la columna de Precio Total */
            td:nth-child(5) {
                background-color: white;
            }
            /* Estilo para la fecha y hora */
            .date-time {
                margin-top: 20px;
                text-align: right;
                color: #666f88;
                font-weight: bold;
            }
            .eslogan{
                text-align: center;
                justify-content: center;
                align-items: center;
                color: #334257;
                font-size: 25px;
                margin-top: 40px;
            }
            .Gracias{
                text-align: center;
                justify-content: center;
                align-items: center;
                color: #476072 ;
                font-size: 28px;
            }
            footer{
                text-align: center;
                color: #C23373;
                background-color: #EBC8E8;
                padding: .8%;
                bottom: 0px;
                width: 100%;
            }
            </style>
            </head>
            <body>
            <br><br>
            <div class="date-time">
            ' . $fecha . '
            <br>
            ' . $hora . '
            </div>
            <br><br><br>
            <header>
                <h1>KALISTA</h1>
            </header>
            <br><br>
            <table>
                <thead>
                    <tr>
                        <th>Descripción</th>
                        <th>Precio</th>
                        <th>Cantidad</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>';
                foreach ($productos as $producto):
                    $query = "UPDATE productos SET cantidad = cantidad -". $producto['cont']." WHERE id =". $producto['id'];
                    mysqli_query($con,  $query);
                    $multi = $producto['precio'] * $producto['cont'];
                    $suma += $multi;
                    $html.='<tr>
                    <td>'.$producto["descripcion"].'</td>
                    <td>$'.$producto["precio"].'</td>
                    <td>'.$producto["cont"].'</td>
                    <td>$'.$multi.'</td>
                   </tr>';
                endforeach;
                $html.='
                    <tr class="total-row">
                        <td colspan="3">Precio Total:</td>
                        <td>$'.$suma.'</td>
                    </tr>
                </tbody>
                </table>
                <div class="eslogan">
                    ¡Felicidades por tu elección floral!
                    <br>
                    Con cariño, preparamos tu compra para que
                    <br>
                    disfrutes de la frescura y la belleza 
                    <br>
                    en cada pétalo.
                </div>
                <br>
                <div class="Gracias">
                    Gracias por su compra.
                </div>
                <br><br><br>
                <footer>
                <h4>Paola Guadalupe Perez Ramirez 
                    4ºP
                    Base de Datos y Desarrollo Web
                </h4>
            </footer>
            </body>
            </html>';

    $dompdf->loadHtml($html);
    $dompdf->render();
    $content = $dompdf->output();
    $file_name = 'Arreglo.pdf';
    file_put_contents($file_name, $content);

    $mail->addAttachment($file_name, 'Arreglo.pdf');
    $mail->Body = "Productos";
    $mail->AltBody = 'Body in plain text for non-HTML mail clients';
    $mail->send();

    foreach ($productos as $producto):
        $query = "INSERT INTO compra (id_usuario, id_producto, cantidad, precio) VALUES (". $producto['id_usuario'].",
        ". $producto['id_producto'].", ". $producto['cont'].", ". $producto['precio'] * $producto['cont'].")";
        mysqli_query($con,  $query);
    endforeach;

    $sqlProductos = "DELETE FROM carrito_usuarios WHERE id_usuario = $usuario";
    $resultado = mysqli_query($con, $sqlProductos);

    echo "<br>Mail has been sent successfully!";
    header('location:ComentariosV.php');
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
?>
