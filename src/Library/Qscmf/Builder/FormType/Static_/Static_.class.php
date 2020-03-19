<?php
namespace Qscmf\Builder\FormType\Static_;

use Qscmf\Builder\FormType\FormType;
use Think\View;

class Static_ implements FormType {

    public function build($form_type){
        $view = new View();
        $view->assign('form', $form_type);
        $content = $view->fetch(__DIR__ . '/static.html');
        return $content;
    }
}