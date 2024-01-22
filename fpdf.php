<?php
include "conexion.php";
session_start();

$usuario = $_SESSION['id'];
$sqlProductos = "SELECT * FROM carrito_usuarios INNER JOIN productos ON productos.id = id_producto
WHERE id_usuario = $usuario";
$resultado = mysqli_query($con, $sqlProductos);

if ($resultado) {
    $productos = mysqli_fetch_all($resultado, MYSQLI_ASSOC);
} else {
    $productos = array(); 
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '/Applications/XAMPP/xamppfiles/htdocs/Conexion/vendor/phpmailer/phpmailer/src/Exception.php';
require '/Applications/XAMPP/xamppfiles/htdocs/Conexion/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '/Applications/XAMPP/xamppfiles/htdocs/Conexion/vendor/phpmailer/phpmailer/src/SMTP.php';
require '/Applications/XAMPP/xamppfiles/htdocs/Conexion/fpdf/fpdf.php';

$mail = new PHPMailer(true);
$correo = $_SESSION['correo'];

echo $correo;

// Crear un PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);

// Header
$pdf->Ln(22);
//$pdf->SetFillColor(193, 168, 183); // Background color for the header
$pdf->SetFillColor(193, 113, 154); 
$pdf->SetTextColor(150, 0, 50);
$pdf->SetFont('', 'B', 24);
$pdf->Cell(0, 18, 'KALISTA', 0, 1, 'C', true); // Centered title

// Table header
$pdf->Ln(15);
//$pdf->SetFillColor(163, 168, 183); // Background color for header row
list($r, $g, $b) = sscanf("#a3a8b7", "#%02x%02x%02x");
// Establecer el color de fondo en FPDF
$pdf->SetFillColor($r, $g, $b);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 15);
$pdf->Cell(100, 12, 'Descripcion', 1, 0, 'r', true);
$pdf->Cell(30, 12, 'Precio', 1, 0, 'r', true);
$pdf->Cell(30, 12, 'Cantidad', 1, 0, 'r', true);
$pdf->Cell(30, 12, 'Total', 1, 1, 'r', true);

// Table content
foreach ($productos as $producto) {
    $query = "UPDATE productos SET cantidad = cantidad -". $producto['cont']." WHERE id =". $producto['id'];
    mysqli_query($con,  $query);
    $multi = $producto['precio'] * $producto['cont'];
    $suma += $multi;
    
    list($r, $g, $b) = sscanf("#E8E8E8", "#%02x%02x%02x");
    // Establecer el color de fondo en FPDF
    $pdf->SetFillColor($r, $g, $b);

    $pdf->SetFont('Arial', '', 13);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(100, 10, $producto['descripcion'], 1, 0);
    $pdf->Cell(30, 10, '$' . $producto['precio'], 1, 0);
    $pdf->Cell(30, 10, $producto['cont'], 1, 0);
    $pdf->Cell(30, 10, '$' . ($multi), 1, 1);
}

// Total row 
$pdf->SetFont('Arial', 'B', 13);
$pdf->SetTextColor(0, 0, 0);
list($r, $g, $b) = sscanf("#E8E8E8", "#%02x%02x%02x");
// Establecer el color de fondo en FPDF
$pdf->SetFillColor($r, $g, $b);
$pdf->Cell(160, 10, 'Precio Total:', 1, 0, 'r', true);
$pdf->Cell(30, 10, '$' . $suma, 1, 0, 1, 'R');

// Additional content
$pdf->Ln(23); // Add some space
list($r, $g, $b) = sscanf("#334257", "#%02x%02x%02x");
// Establecer el color del texto en FPDF
$pdf->SetTextColor($r, $g, $b);
$pdf->SetFont('Arial', '', 18);
$pdf->Cell(0, 10, 'Felicidades por tu eleccion floral', 0, 1, 'C');
$pdf->Cell(0, 10, 'Con afecto, preparamos tu compra para que disfrutes de ', 0, 1, 'C');
$pdf->Cell(0, 10, 'la frescura y la belleza en cada petalo.', 0, 1, 'C');


$pdf->Ln(10); // Add more space
$pdf->Cell(0, 10, 'Gracias por su compra.', 0, 1, 'C');

// Output PDF
$pdfPath = '/Applications/XAMPP/xamppfiles/htdocs/Conexion/kalista.pdf';
$pdf->Output($pdfPath, 'F');


// Instancia de PHPMailer
$mail = new PHPMailer(true);

try {
    // Configuración del servidor SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'paola.permz@gmail.com';
    $mail->Password = 'gomh deze ftby kpun';
    $mail->SMTPSecure = 'tls'; // Puedes cambiar a 'ssl' si es necesario
    $mail->Port = 587; // Puerto SMTP

    // Remitente y destinatario
    $mail->setFrom('paola.permz@gmail.com', 'Kalista');
    $mail->addAddress($correo, 'usuarios');

    // Contenido del correo
    $mail->isHTML(true);
    $mail->Subject = 'Arreglo';
    $mail->Body    = 'Kalista Arreglo';

    // Adjuntar el archivo PDF
    $mail->addAttachment($pdfPath);

    // Envía el correo
    $mail->send();

    foreach($productos as $producto) {
        $query = "INSERT INTO compra (id_usuario, id_producto, cantidad, precio) VALUES (". $producto['id_usuario'].",
        ". $producto['id_producto'].", ". $producto['cont'].", ". $producto['precio'] * $producto['cont'].")";
        echo $query;
        mysqli_query($con,  $query);
    }

    $sqlProductos = "DELETE FROM carrito_usuarios WHERE id_usuario = $usuario";
    $resultado = mysqli_query($con, $sqlProductos);

    // Eliminar el archivo temporal después de enviar el correo
    unlink($pdfPath);

    echo 'Correo enviado con éxito';
    header('location:ComentariosV.php');
} catch (Exception $e) {
    echo "Error al enviar el correo: {$mail->ErrorInfo}";
}
?>