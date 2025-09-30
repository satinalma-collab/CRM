<?php
require_once __DIR__ . '/../includes/lib/fpdf.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/functions.php';

// Güvenlik: Sadece giriş yapmış kullanıcıların PDF indirebilmesini sağlamak için
session_start();
require_login();

// Token'ı URL'den al
$token = $_GET['token'] ?? '';
if (empty($token)) {
    die('Geçersiz istek.');
}

$pdo = get_db_connection();

// Teklif, müşteri ve organizasyon bilgilerini getir
$stmt = $pdo->prepare(
    "SELECT p.*, c.name as customer_name, c.address as customer_address, o.name as org_name
     FROM proposals p
     JOIN customers c ON p.customer_id = c.id
     JOIN organizations o ON p.organization_id = o.id
     WHERE p.share_token = ? AND p.organization_id = ?"
);
$stmt->execute([$token, $_SESSION['organization_id']]);
$proposal = $stmt->fetch();

if (!$proposal) {
    die('Teklif bulunamadı veya bu teklifi görüntüleme yetkiniz yok.');
}

// Teklif kalemlerini getir
$items_stmt = $pdo->prepare("SELECT * FROM proposal_items WHERE proposal_id = ? ORDER BY id");
$items_stmt->execute([$proposal['id']]);
$items = $items_stmt->fetchAll();


// --- PDF Oluşturma ---
class PDF extends FPDF {
    // Sayfa başlığı
    function Header() {
        // Bu örnekte basit tutulmuştur, istenirse logo eklenebilir
        $this->SetFont('Arial','B',15);
        $this->Cell(80);
        $this->Cell(30,10,'Teklif',1,0,'C');
        $this->Ln(20);
    }

    // Sayfa alt bilgisi
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Sayfa '.$this->PageNo().'/{nb}',0,0,'C');
    }
}

// PDF nesnesini oluştur
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','',12);

// Türkçe karakter sorunu için basit bir çözüm (daha gelişmiş kütüphaneler daha iyi sonuç verir)
function fix_encoding($string) {
    return iconv('UTF-8', 'ISO-8859-9//TRANSLIT', $string);
}

// Başlık ve Müşteri Bilgileri
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,10, fix_encoding($proposal['org_name']));
$pdf->Ln();
$pdf->SetFont('','',12);
$pdf->Cell(0,7, 'Teklif No: #' . $proposal['id']);
$pdf->Ln();
$pdf->Cell(0,7, 'Tarih: ' . date('d.m.Y', strtotime($proposal['proposal_date'])));
$pdf->Ln(10);

$pdf->SetFont('','B');
$pdf->Cell(0,7, 'Musteri Bilgileri');
$pdf->Ln();
$pdf->SetFont('','');
$pdf->Cell(0,7, fix_encoding($proposal['customer_name']));
$pdf->Ln();
$pdf->MultiCell(0,7, fix_encoding($proposal['customer_address']));
$pdf->Ln(10);


// Teklif Kalemleri Tablosu
$pdf->SetFont('','B');
$pdf->Cell(100,7, 'Urun/Hizmet',1);
$pdf->Cell(25,7, 'Miktar',1,0,'R');
$pdf->Cell(30,7, 'Birim Fiyat',1,0,'R');
$pdf->Cell(35,7, 'Toplam',1,0,'R');
$pdf->Ln();

$pdf->SetFont('','');
$subtotal = 0;
foreach($items as $item) {
    $line_total = $item['quantity'] * $item['unit_price'] * (1 - $item['discount_percentage']/100);
    $subtotal += $line_total;
    $pdf->Cell(100,7, fix_encoding($item['name']),1);
    $pdf->Cell(25,7, $item['quantity'] . ' ' . fix_encoding($item['unit']),1,0,'R');
    $pdf->Cell(30,7, number_format($item['unit_price'], 2, ',', '.') . ' TL',1,0,'R');
    $pdf->Cell(35,7, number_format($line_total, 2, ',', '.') . ' TL',1,0,'R');
    $pdf->Ln();
}

// Toplam
$pdf->SetFont('','B');
$pdf->Cell(155,8, 'Genel Toplam',1,0,'R');
$pdf->Cell(35,8, number_format($proposal['total_amount'], 2, ',', '.') . ' TL',1,0,'R');
$pdf->Ln();


// PDF'i tarayıcıya gönder
$pdf->Output('D', 'Teklif-'.$proposal['id'].'.pdf');
?>