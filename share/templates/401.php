<?php
class NoAuthView
{
    public function pageTitle()
    {
        return 'Not authorized';
    }
}

ob_start();
?>
<p>
    The credentials provided for the Web source, are invalid.
</p>
<?php
$view = new NoAuthView();
$content = ob_get_clean();

header('WWW-Authenticate: Basic realm="Pocs"');
header('HTTP/1.0 401 Unauthorized');

include __DIR__ . '/layout.php';
