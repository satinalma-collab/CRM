<?php
require_once __DIR__ . '/../includes/lib/fpdf.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
require_login();

$token = $_GET['token'] ?? '';
if (empty($token)) die('Geçersiz istek.');

$pdo = get_db_connection();

// Teklif, müşteri ve organizasyon bilgilerini getir
$stmt = $pdo->prepare(
    "SELECT p.*, c.name as customer_name, c.address as customer_address, o.name as org_name, o.logo_url
     FROM proposals p
     JOIN customers c ON p.customer_id = c.id
     JOIN organizations o ON p.organization_id = o.id
     WHERE p.share_token = ? AND p.organization_id = ?"
);
$stmt->execute([$token, $_SESSION['organization_id']]);
$proposal = $stmt->fetch();

if (!$proposal) die('Teklif bulunamadı veya yetkiniz yok.');

// Teklif kalemlerini ve ürün görsellerini getir
$items_stmt = $pdo->prepare(
    "SELECT i.*, pr.image_url
     FROM proposal_items i
     LEFT JOIN products pr ON i.product_id = pr.id
     WHERE i.proposal_id = ? ORDER BY i.id"
);
$items_stmt->execute([$proposal['id']]);
$items = $items_stmt->fetchAll();


// --- PDF Oluşturma ---
class ProposalPDF extends FPDF {
    private $org_name = 'HasCRM';
    private $logo_url = '';

    function setOrgDetails($name, $logo) {
        $this->org_name = $this->fix_encoding($name);
        $this->logo_url = $logo;
    }

    function fix_encoding($string) {
        return iconv('UTF-8', 'ISO-8859-9//TRANSLIT', $string);
    }

    function Header() {
        // Logo
        if ($this->logo_url && file_exists(__DIR__ . '/..' . $this->logo_url)) {
            $this->Image(__DIR__ . '/..' . $this->logo_url, 10, 6, 30);
        }
        // Şirket Adı
        $this->SetFont('Arial','B',20);
        $this->Cell(80);
        $this->Cell(30,10, $this->org_name, 0, 0, 'C');
        $this->Ln(25);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10, $this->fix_encoding('Sayfa ') . $this->PageNo() . '/{nb}',0,0,'C');
    }

    function ChapterTitle($label, $value) {
        $this->SetFont('Arial','B',12);
        $this->Cell(40, 6, $this->fix_encoding($label), 0);
        $this->SetFont('','');
        $this->Cell(0, 6, $this->fix_encoding($value), 0);
        $this->Ln();
    }

    function FancyTable($header, $data) {
        // Renkler ve yazı tipleri
        $this->SetFillColor(240, 240, 240);
        $this->SetTextColor(0);
        $this->SetDrawColor(200, 200, 200);
        $this->SetFont('','B');

        // Başlık
        $w = array(80, 25, 40, 45); // Sütun genişlikleri
        for($i=0; $i<count($header); $i++)
            $this->Cell($w[$i], 7, $this->fix_encoding($header[$i]), 1, 0, 'C', true);
        $this->Ln();

        // Veri
        $this->SetFont('');
        foreach($data as $row) {
            $this->Cell($w[0], 20, '', 'LR'); // Görsel için boşluk
            $this->SetX(10); // Başa dön

            // Ürün resmi
            if(!empty($row['image_url']) && file_exists(__DIR__ . '/..' .$row['image_url'])) {
                $this->Image(__DIR__ . '/..' .$row['image_url'], 11, $this->GetY()+1, 18, 18);
            }

            $this->Cell($w[0], 20, $this->fix_encoding("  " . $row['name']), 'LR');
            $this->Cell($w[1], 20, $row['quantity'] . ' ' . $this->fix_encoding($row['unit']), 'LR', 0, 'R');
            $this->Cell($w[2], 20, number_format($row['unit_price'], 2, ',', '.') . ' ' . $proposal['currency'], 'LR', 0, 'R');
            $this->Cell($w[3], 20, number_format($row['line_total'], 2, ',', '.') . ' ' . $proposal['currency'], 'LR', 0, 'R');
            $this->Ln();
        }
        $this->Cell(array_sum($w), 0, '', 'T'); // Kapanış çizgisi
    }
}

$pdf = new ProposalPDF();
$pdf->setOrgDetails($proposal['org_name'], $proposal['logo_url']);
$pdf->AliasNbPages();
$pdf->AddPage();

// Teklif Bilgileri
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0, 10, $pdf->fix_encoding($proposal['title']), 0, 1, 'C');
$pdf->Ln(10);

// Müşteri ve Teklif Detayları
$pdf->SetFont('Arial','',12);
$pdf->ChapterTitle('Müşteri:', $proposal['customer_name']);
$pdf->ChapterTitle('Teklif No:', $proposal['id']);
$pdf->ChapterTitle('Teklif Tarihi:', date('d.m.Y', strtotime($proposal['proposal_date'])));
$pdf->ChapterTitle('Geçerlilik:', date('d.m.Y', strtotime($proposal['valid_until_date'])));
$pdf->Ln(10);

// Tablo Başlığı
$header = array('Ürün/Hizmet', 'Miktar', 'Birim Fiyat', 'Toplam');
// Tablo için veriyi hazırla
$table_data = [];
foreach($items as $item) {
    $table_data[] = [
        'name' => $item['name'],
        'quantity' => $item['quantity'],
        'unit' => $item['unit'],
        'unit_price' => $item['unit_price'],
        'line_total' => $item['line_total'],
        'image_url' => $item['image_url'],
    ];
}
$pdf->FancyTable($header, $table_data);

// Toplamlar
$pdf->SetX($pdf->GetX() + 105); // Sağa hizala
$pdf->SetFont('','B');
$pdf->Cell(40, 8, 'Genel Toplam:', 1, 0, 'R');
$pdf->SetFont('');
$pdf->Cell(45, 8, number_format($proposal['total_amount'], 2, ',', '.') . ' ' . $proposal['currency'], 1, 1, 'R');
$pdf->Ln(10);

// Şartlar
if (!empty($proposal['delivery_terms']) || !empty($proposal['payment_terms'])) {
    $pdf->SetFont('','B');
    $pdf->Cell(0, 7, $pdf->fix_encoding('Şartlar ve Koşullar'), 0, 1);
    $pdf->SetFont('','');
    if(!empty($proposal['delivery_terms'])) $pdf->MultiCell(0, 5, $pdf->fix_encoding("- Teslimat: " . $proposal['delivery_terms']));
    if(!empty($proposal['payment_terms'])) $pdf->MultiCell(0, 5, $pdf->fix_encoding("- Ödeme: " . $proposal['payment_terms']));
}

$pdf->Output('D', 'Teklif-'.$proposal['id'].'.pdf');
?>