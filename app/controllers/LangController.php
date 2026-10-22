<?php
declare(strict_types=1);

class LangController extends Controller
{
    /** index.php?r=lang/switch&to=bn */
    public function switch(): void
    {
        $lang = $this->input('to') === 'bn' ? 'bn' : 'en';
        Session::set('lang', $lang);
        if ($user = Auth::user()) {
            (new User())->setLanguage((int)$user['id'], $lang);
        }
        Lang::set($lang);
        if ($this->isAjax()) {
            $this->json(['ok' => true, 'lang' => $lang]);
        }
        $this->redirectTo($this->backUrl('home/index'));
    }

    /** Bangla dictionary for JavaScript strings (cached by the browser). */
    public function js(): void
    {
        header('Content-Type: application/javascript; charset=utf-8');
        header('Cache-Control: public, max-age=86400');
        $file = APP_PATH . '/lang/bn.php';
        header('ETag: "' . (is_file($file) ? filemtime($file) : 0) . '"');
        echo 'window.AGRI_BN=' . json_encode(Lang::dictionary(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';';
        exit;
    }
}
