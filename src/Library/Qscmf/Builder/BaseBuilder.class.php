<?php

namespace Qscmf\Builder;

use Qscmf\Lib\DBCont;
use Think\Controller;

class BaseBuilder extends Controller
{
    protected $_nid;
    protected $_meta_title;
    /**
     * @var 模版
     */
    protected $_template;
    /**
     * @var 额外功能代码
     */
    protected $_extra_html;
    /**
     * @var array
     */
    protected $_tab_nav=[];

    // 顶部自定义html代码
    protected $_top_html;
    // content 底部自定义html
    protected $_content_bottom = [];

    private array $_exists_column_name = [];

    protected string $_gid;

    public function setNIDByNode($module = MODULE_NAME, $controller = CONTROLLER_NAME, $action = 'index'){
        $module_ent = D('Node')->where(['name' => $module, 'level' => DBCont::LEVEL_MODULE, 'status' => DBCont::NORMAL_STATUS])->find();

        if(!$module_ent){
            E('setNIDByNode 传递的参数module不存在');
        }

        $controller_ent = D('Node')->where(['name' => $controller, 'level' => DBCont::LEVEL_CONTROLLER, 'status' => DBCont::NORMAL_STATUS, 'pid' => $module_ent['id']])->find();
        if(!$controller_ent){
            E('setNIDByNode 传递的参数controller不存在');
        }

        $action_ent = D('Node')->where(['name' => $action, 'level' => DBCont::LEVEL_ACTION, 'status' => DBCont::NORMAL_STATUS, 'pid' => $controller_ent['id']])->find();
        if(!$action_ent){
            E('setNIDByNode 传递的参数action不存在');
        }
        else{
            return $this->setNID($action_ent['id']);
        }
    }


    public function setNID($nid){
        $this->_nid = $nid;
        return $this;
    }


    /**
     * 设置页面标题
     * @param $title 标题文本
     * @return $this
     */
    public function setMetaTitle($meta_title) {
        $this->_meta_title = $meta_title;
        return $this;
    }




    /**
     * 设置Tab按钮列表
     * @param $tab_list Tab列表  array(
    'title' => '标题',
    'href' => 'http://www.corethink.cn'
    )
     * @param $current_tab 当前tab
     * @return $this
     */
    public function setTabNav($tab_list, $current_tab) {
        $this->_tab_nav = array(
            'tab_list' => $tab_list,
            'current_tab' => $current_tab
        );
        return $this;
    }

    /**
     * 设置额外功能代码
     * @param $extra_html 额外功能代码
     * @return $this
     */
    public function setExtraHtml($extra_html) {
        $this->_extra_html = $extra_html;
        return $this;
    }

    /**
     * 设置页面模版
     * @param $template 模版
     * @return $this
     */
    public function setTemplate($template) {
        $this->_template = $template;
        return $this;
    }

    /**
     * 设置页面顶部自定义html代码
     * @param $top_html 顶部自定义html代码
     * @return $this
     */
    public function setTopHtml($top_html){
        $this->_top_html = $top_html;
        return $this;
    }

    /**
     * 检测字段的权限点，无权限则unset该item
     *
     * @param array $check_items
     * @return array
     */
    public function checkAuthNode($check_items){
        return BuilderHelper::checkAuthNode($check_items);
    }

    public function addContentBottom($html){
        array_push($this->_content_bottom, $html);
        return $this;
    }

    protected function appendColumnName($name):self|\Exception{
        if (!in_array($name, $this->_exists_column_name)){
            $this->_exists_column_name[] = $name;
            return $this;
        }else{
            E($name." 该字段已存在");
        }
    }

    public function setGid($gid):self{
        $this->_gid = $gid;
        return  $this;
    }

    public function getGid():string{
        return  $this->_gid;
    }

}