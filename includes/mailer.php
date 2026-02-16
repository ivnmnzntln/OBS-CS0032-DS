<?php
/**
 * SMTP Mailer helper using PHPMailer
 */

function sendOrderConfirmation($toEmail, $toName, $orderId, $items, $subtotal, $tax, $total, $trackingNumber) {
    if (empty(SMTP_USER) || empty(SMTP_PASS) || empty(SMTP_FROM_EMAIL)) {
        return false; // SMTP not configured
    }

    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoload)) {
        return false; // PHPMailer not installed
    }
    require_once $autoload;

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = 'Order Confirmation #' . $orderId;

        $itemsHtml = '';
        foreach ($items as $item) {
            $lineTotal = number_format($item['price'] * $item['quantity'], 2);
            $itemsHtml .= '<tr>'
                . '<td style="padding:6px 8px;">' . htmlspecialchars($item['title'], ENT_QUOTES) . '</td>'
                . '<td style="padding:6px 8px; text-align:center;">' . (int)$item['quantity'] . '</td>'
                . '<td style="padding:6px 8px; text-align:right;">$' . $lineTotal . '</td>'
                . '</tr>';
        }

        $mail->Body = '
            <h2>Thank you for your order!</h2>
            <p>Your order <strong>#' . $orderId . '</strong> has been placed successfully.</p>
            <p><strong>Tracking:</strong> ' . htmlspecialchars($trackingNumber, ENT_QUOTES) . '</p>
            <table style="border-collapse:collapse; width:100%;" border="1" cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th style="padding:6px 8px; text-align:left;">Item</th>
                        <th style="padding:6px 8px; text-align:center;">Qty</th>
                        <th style="padding:6px 8px; text-align:right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    ' . $itemsHtml . '
                </tbody>
            </table>
            <p><strong>Subtotal:</strong> $' . number_format($subtotal, 2) . '</p>
            <p><strong>Tax:</strong> $' . number_format($tax, 2) . '</p>
            <p><strong>Grand Total:</strong> $' . number_format($total, 2) . '</p>
        ';

        $mail->AltBody = "Order #$orderId placed. Total: $" . number_format($total, 2);

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
