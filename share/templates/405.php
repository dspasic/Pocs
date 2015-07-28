<?php
class HttpMethodNotAllowedView
{
    public function pageTitle()
    {
        return 'HTTP Method not allowed';
    }
}

$view = new HttpMethodNotAllowedView();

ob_start();
?>
    <p>
        The credentials provided for the Web source, are invalid.
        <p>Allowed methods are [<?php echo implode(',', $allowedMethods) ?>]</p>
    </p>
<?php
$content = ob_get_clean();

include __DIR__ . '/layout.php';
