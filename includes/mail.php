<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function send_mail(string $to, string $subject, string $html, ?string $replyTo = null): bool
{
    if (!is_dir(MAIL_LOG_PATH)) {
        mkdir(MAIL_LOG_PATH, 0775, true);
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . SMTP_FROM_NAME . ' <' . SITE_EMAIL . '>',
    ];
    if ($replyTo) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }

    $logged = log_mail($to, $subject, $html);
    $sent = false;

    if (SMTP_ENABLED && SMTP_HOST && SMTP_USER) {
        $sent = smtp_send($to, $subject, $html, $replyTo);
    } else {
        $sent = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $html, implode("\r\n", $headers));
    }

    return $logged || $sent;
}

function log_mail(string $to, string $subject, string $html): bool
{
    $stamp = (new DateTimeImmutable())->format('Ymd_His_u');
    $safeTo = preg_replace('/[^a-zA-Z0-9@._-]+/', '_', $to) ?: 'mail';
    $file = MAIL_LOG_PATH . '/' . $stamp . '_' . $safeTo . '_' . bin2hex(random_bytes(3)) . '.html';
    $body = '<p><strong>Za:</strong> ' . h($to) . '<br><strong>Zadeva:</strong> ' . h($subject) . '</p><hr>' . $html;
    return file_put_contents($file, $body) !== false;
}

function email_layout(string $title, string $bodyHtml): string
{
    return '<!DOCTYPE html><html lang="sl"><head><meta charset="UTF-8"><title>'
        . h($title) . '</title></head><body style="margin:0;background:#f4efe6;font-family:Georgia,serif;color:#1c1917;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4efe6;padding:32px 12px;">'
        . '<tr><td align="center"><table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#fffcf7;border-radius:16px;overflow:hidden;">'
        . '<tr><td style="background:#234139;color:#f4efe6;padding:28px 32px;font-size:22px;letter-spacing:.08em;text-transform:uppercase;">'
        . h(SITE_NAME) . '</td></tr>'
        . '<tr><td style="padding:32px;line-height:1.6;font-size:16px;">' . $bodyHtml . '</td></tr>'
        . '<tr><td style="padding:0 32px 32px;color:#6b6358;font-size:13px;">'
        . h(SITE_LOCATION) . '<br>' . h(SITE_EMAIL) . ' · ' . h(SITE_PHONE)
        . '</td></tr></table></td></tr></table></body></html>';
}

function notify_reservation(array $reservation, array $apartment): void
{
    $nights = nights_between($reservation['check_in'], $reservation['check_out']);
    $guestName = $reservation['first_name'] . ' ' . $reservation['last_name'];
    $details = '<p>Pozdravljeni ' . h($reservation['first_name']) . ',</p>'
        . '<p>prejeli smo vašo rezervacijo za <strong>' . h($apartment['name']) . '</strong>.</p>'
        . '<table style="width:100%;border-collapse:collapse;font-size:15px;">'
        . row_html('Apartma', $apartment['name'])
        . row_html('Prihod', slovenian_date($reservation['check_in']) . ($reservation['arrival_time'] ? ' ob ' . $reservation['arrival_time'] : ''))
        . row_html('Odhod', slovenian_date($reservation['check_out']))
        . row_html('Nočitve', (string) $nights)
        . row_html('Gostje', (string) $reservation['guests'])
        . row_html('Skupaj', money_eur((float) $reservation['total_price']))
        . row_html('Telefon', $reservation['phone'])
        . '</table>'
        . '<p>Status: <strong>v obdelavi</strong>. Kmalu vam potrdimo razpoložljivost.</p>';

    $guestHtml = email_layout('Potrdilo povpraševanja', $details);
    $adminHtml = email_layout('Nova rezervacija',
        '<p>Nova rezervacija prek spletne strani.</p>'
        . '<p><strong>' . h($guestName) . '</strong> · ' . h($reservation['email']) . '</p>'
        . $details
        . ($reservation['message'] ? '<p>Sporočilo: ' . nl2br(h($reservation['message'])) . '</p>' : '')
    );

    send_mail($reservation['email'], 'Vaša rezervacija · ' . $apartment['name'], $guestHtml, SITE_EMAIL);
    send_mail(ADMIN_NOTIFY_EMAIL, 'Nova rezervacija · ' . $apartment['name'] . ' · ' . $guestName, $adminHtml, $reservation['email']);
}

function notify_transfer(array $transfer): void
{
    $guestName = $transfer['first_name'] . ' ' . $transfer['last_name'];
    $direction = transfer_direction_label($transfer['direction']);
    $details = '<p>Pozdravljeni ' . h($transfer['first_name']) . ',</p>'
        . '<p>prejeli smo vaše naročilo prevoza <strong>' . h($direction) . '</strong>.</p>'
        . '<table style="width:100%;border-collapse:collapse;font-size:15px;">'
        . row_html('Smer', $direction)
        . row_html('Tip vozila', $transfer['vehicle_type'] === 'private' ? 'Zasebni prevoz' : 'Skupni prevoz')
        . row_html('Prevzem', $transfer['pickup'])
        . row_html('Izstop', $transfer['dropoff'])
        . row_html('Odhod', slovenian_date($transfer['date_outbound']) . ' ob ' . $transfer['time_outbound'])
        . ($transfer['date_return'] ? row_html('Vrnitev', slovenian_date($transfer['date_return']) . ' ob ' . ($transfer['time_return'] ?? '')) : '')
        . row_html('Potniki', (string) $transfer['passengers'] . ($transfer['children'] ? ' (+ ' . $transfer['children'] . ' otrok)' : ''))
        . row_html('Prtljaga', (string) $transfer['luggage'])
        . ($transfer['flight_number'] ? row_html('Št. leta', $transfer['flight_number']) : '')
        . row_html('Cena', money_eur((float) $transfer['price']))
        . '</table>'
        . '<p>Status: <strong>v obdelavi</strong>. Voznik in točen čas vam sporočimo po e-pošti.</p>';

    $guestHtml = email_layout('Prevoz na/iz letališča', $details);
    $adminHtml = email_layout('Nov prevoz',
        '<p>Novo naročilo prevoza.</p>'
        . '<p><strong>' . h($guestName) . '</strong> · ' . h($transfer['email']) . ' · ' . h($transfer['phone']) . '</p>'
        . $details
        . ($transfer['message'] ? '<p>Opomba: ' . nl2br(h($transfer['message'])) . '</p>' : '')
    );

    send_mail($transfer['email'], 'Vaš prevoz · ' . $direction, $guestHtml, SITE_EMAIL);
    send_mail(ADMIN_NOTIFY_EMAIL, 'Nov prevoz · ' . $direction . ' · ' . $guestName, $adminHtml, $transfer['email']);
}

function row_html(string $label, string $value): string
{
    return '<tr><td style="padding:8px 0;border-bottom:1px solid #e8dcc8;color:#6b6358;width:40%;">'
        . h($label) . '</td><td style="padding:8px 0;border-bottom:1px solid #e8dcc8;">'
        . h($value) . '</td></tr>';
}

function smtp_send(string $to, string $subject, string $html, ?string $replyTo = null): bool
{
    $host = SMTP_HOST;
    $port = SMTP_PORT;
    $prefix = SMTP_SECURE === 'ssl' ? 'ssl://' : '';
    $fp = @stream_socket_client($prefix . $host . ':' . $port, $errno, $errstr, 20);
    if (!$fp) {
        return false;
    }

    $read = static fn() => fgets($fp, 515);
    $cmd = static function (string $line) use ($fp): void {
        fwrite($fp, $line . "\r\n");
    };

    $read();
    $cmd('EHLO ' . ($host ?: 'localhost'));
    $ehlo = '';
    while ($line = $read()) {
        $ehlo .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }
    if (SMTP_SECURE === 'tls') {
        $cmd('STARTTLS');
        $read();
        stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        $cmd('EHLO ' . ($host ?: 'localhost'));
        while ($line = $read()) {
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
    }

    $cmd('AUTH LOGIN');
    $read();
    $cmd(base64_encode(SMTP_USER));
    $read();
    $cmd(base64_encode(SMTP_PASS));
    $auth = $read();
    if (!str_starts_with((string) $auth, '235')) {
        fclose($fp);
        return false;
    }

    $cmd('MAIL FROM: <' . SITE_EMAIL . '>');
    $read();
    $cmd('RCPT TO: <' . $to . '>');
    $read();
    $cmd('DATA');
    $read();
    $headers = 'From: ' . SMTP_FROM_NAME . ' <' . SITE_EMAIL . ">\r\n";
    if ($replyTo) {
        $headers .= 'Reply-To: ' . $replyTo . "\r\n";
    }
    $headers .= 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";
    $headers .= 'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=' . "\r\n";
    $headers .= 'To: <' . $to . ">\r\n\r\n";
    fwrite($fp, $headers . $html . "\r\n.\r\n");
    $read();
    $cmd('QUIT');
    fclose($fp);
    return true;
}
