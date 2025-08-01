<?php

namespace Qscmf\Builder;

use Bootstrap\RegisterContainer;
use Illuminate\Support\Str;
use Qscmf\Builder\ButtonType\Addnew\Addnew;
use Qscmf\Builder\ButtonType\Delete\Delete;
use Qscmf\Builder\ButtonType\Forbid\Forbid;
use Qscmf\Builder\ButtonType\Resume\Resume;
use Qscmf\Builder\ButtonType\Save\DefaultEditableColumn;
use Qscmf\Builder\ButtonType\Save\Save;
use Qscmf\Builder\ButtonType\Self\SelfButton;
use Qscmf\Builder\ColumnType\Btn\Btn;
use Qscmf\Builder\ColumnType\EditableInterface;
use Qscmf\Builder\ColumnType\Num\Num;
use Qscmf\Builder\ListRightButton\Edit\Edit;
use Qscmf\Builder\ListSearchType\DateRange\DateRange;
use Qscmf\Builder\ListSearchType\Select\Select;
use Qscmf\Builder\ListSearchType\SelectCity\SelectCity;
use Qscmf\Builder\ListSearchType\SelectText\SelectText;
use Qscmf\Builder\ListSearchType\Text\Text;
use Qscmf\Builder\ListSearchType\Hidden\Hidden;
use Qscmf\Builder\ListSearchType\Self\Self_ as SelfSearch;
use Qscmf\Builder\ColumnType\Status\Status;
use Qscmf\Builder\ColumnType\A\A;
use Qscmf\Builder\ColumnType\Date\Date;
use Qscmf\Builder\ColumnType\Fun\Fun;
use Qscmf\Builder\ColumnType\Icon\Icon;
use Qscmf\Builder\ColumnType\Picture\Picture;
use Qscmf\Builder\ColumnType\Self\Self_;
use Qscmf\Builder\ColumnType\Time\Time;
use Qscmf\Builder\ColumnType\Type\Type;

/**
 * 数据列表自动生成器
 */
class ListBuilder extends BaseBuilder implements \Qscmf\Builder\GenButton\IGenButton, \Qscmf\Builder\GenColumn\IGenColumn {
    use \Qscmf\Builder\GenButton\TGenButton;
    use \Qscmf\Builder\GenColumn\TGenColumn;

    private $_top_button_list = array();   // 顶部工具栏按钮组
    private $_search  = array();           // 搜索参数配置
    private $_search_url;                    //搜索按钮指向url
    private $_table_column_list = array(); // 表格标题字段
    private $_table_data_list   = array(); // 表格数据列表
    private $_primary_key = '_pk';         //备份主键
    private $_table_data_page;             // 表格数据分页
    private $_right_button_list = array(); // 表格右侧操作按钮组
    private $_alter_data_list = array();   // 表格数据列表重新修改的项目
    private $_show_check_box = true;
    private $_attr_callback; //checkbox属性自定义勾子
    private $_meta_button_list = array();  //标题按钮
    private $_lock_row = 1; //锁定标题
    private $_lock_col = 0; //锁定列(左)
    private $_lock_col_right = 0; //锁定列(右)
    private $_page_template; // 页码模板
    private $_top_button_type = [];
    private $_search_type = [];
    private $_list_template;
    private $_origin_table_data_list = [];
    private $_right_btn_def_class = 'qs-list-right-btn';
    private $_hidden_key = "_hidden_pk_";

    static function genCheckBoxDisableCb($data_key, $disable_value){
        return function($attr, $data) use ($data_key, $disable_value){
            if($data[$data_key] == $disable_value){
                $attr['class'] = 'ids-disable';
                $attr['disabled'] = 'disabled';
            }
            return $attr;
        };
    }

    /**
     * 初始化方法
     * @return $this
     */
    protected function _initialize() {
        $module_name = 'Admin';
        $this->_template = __DIR__ .'/Layout/'.$module_name.'/list.html';
        $this->_list_template = __DIR__ . '/listbuilder.html';
        $this->_page_template = __DIR__ .'/Layout/'.$module_name.'/pagination.html';

        self::registerTopButtonType();
        self::registerSearchType();
        self::registerRightButtonType();
        self::registerColumnType();

        $this->setGid(Str::uuid()->getHex());
    }

    public function getDataKeyName(){
        return $this->_table_data_list_key;
    }

    public function setSearchUrl($url){
        $this->_search_url = $url;
        return $this;
    }

    /**
     * 锁定行
     * @param $row int 锁定行数
     * @return $this
     */
    public function setLockRow($row){
        $this->_lock_row = $row;
        return $this;
    }

    /**
     * 锁定列（左）
     * @param $col int 锁定列数
     * @return $this
     */
    public function setLockCol($col){
        $this->_lock_col = $col;
        return $this;
    }

    /**
     * 锁定列（右）
     * @param $col int 锁定列数
     * @return $this
     */
    public function setLockColRight($col){
        $this->_lock_col_right = $col;
        return $this;
    }

    public function setCheckBox($flag, $attr_callback = null){
        $this->_show_check_box = $flag;
        $this->_attr_callback = $attr_callback;
        return $this;
    }

    protected function registerSearchType(){
        static $search_type = [];
        if(empty($search_type)){
            $base_search_type = self::registerBaseSearchType();
            $search_type = array_merge($base_search_type, RegisterContainer::getListSearchType());
        }

        $this->_search_type = $search_type;
    }

    protected function registerBaseSearchType(){
        return [
            'date_range' => DateRange::class,
            'select' => Select::class,
            'select_city' => SelectCity::class,
            'select_text' => SelectText::class,
            'text' => Text::class,
            'hidden' => Hidden::class,
            'self' => SelfSearch::class,
        ];
    }

    protected function registerTopButtonType(){
        static $top_button_type = [];
        if(empty($top_button_type)) {
            $base_top_button_type = self::registerBaseTopButtonType();
            $top_button_type = array_merge($base_top_button_type, RegisterContainer::getListTopButtons());
        }

        $this->_top_button_type = $top_button_type;
    }

    protected function registerBaseTopButtonType(){
        return [
            'addnew' => Addnew::class,
            'delete' => Delete::class,
            'forbid' => Forbid::class,
            'resume' => Resume::class,
            'save' => Save::class,
            'self' => SelfButton::class
        ];
    }

    protected function registerRightButtonType(){
       self::registerButtonType();
    }


    public function addMetaButton($type, $attribute = null, $tips = ''){
        switch ($type) {
            case 'return':  // 添加新增按钮
                // 预定义按钮属性以简化使用
                $my_attribute['title'] = '返回';
                $my_attribute['class'] = 'btn btn-primary';
                $my_attribute['href']  = 'javascript:history.go(-1)';

                /**
                 * 如果定义了属性数组则与默认的进行合并
                 * 用户定义的同名数组元素会覆盖默认的值
                 * 比如$builder->addTopButton('add', array('title' => '换个马甲'))
                 * '换个马甲'这个碧池就会使用山东龙潭寺的十二路谭腿第十一式“风摆荷叶腿”
                 * 把'新增'踢走自己霸占title这个位置，其它的属性同样道理
                 */
                if ($attribute && is_array($attribute)) {
                    $my_attribute = array_merge($my_attribute, $attribute);
                }
                $class = $my_attribute['class'];
                $my_attribute['class'] = 'pull-right ' . $class;
                // 这个按钮定义好了把它丢进按钮池里
                break;
            case 'self': //添加自定义按钮(第一原则使用上面预设的按钮，如果有特殊需求不能满足则使用此自定义按钮方法)
                // 预定义按钮属性以简化使用
                $my_attribute['class'] = 'btn btn-danger';

                // 如果定义了属性数组则与默认的进行合并
                if ($attribute && is_array($attribute)) {
                    $my_attribute = array_merge($my_attribute, $attribute);
                } else {
                    $my_attribute['title'] = '该自定义按钮未配置属性';
                }
                $class = $my_attribute['class'];
                $my_attribute['class'] = 'pull-right ' . $class;
                // 这个按钮定义好了把它丢进按钮池里
                break;
        }
        if($tips != ''){
            $my_attribute['tips'] = $tips;
        }
        $this->_meta_button_list[] = $my_attribute;
        return $this;
    }

    /**
     * 加入一个列表顶部工具栏按钮
     * 在使用预置的几种按钮时，比如我想改变新增按钮的名称
     * 那么只需要$builder->addTopButton('addnew', array('title' => '换个马甲'))
     * 如果想改变地址甚至新增一个属性用上面类似的定义方法
     * @param string $type 按钮类型，取值参考registerBaseTopButtonType
     * @param array|null  $attribute 按钮属性，一个定义标题/链接/CSS类名等的属性描述数组
     * @param string $tips 按钮提示
     * @param string|array $auth_node 按钮权限点
     * @param string|array $options 按钮options
     * @return $this
     */
    public function addTopButton($type, $attribute = null, $tips = '', $auth_node = '', $options = []) {

        $top_button_option['type'] = $type;
        $top_button_option['attribute'] = $attribute;
        $top_button_option['tips'] = $tips;
        $top_button_option['auth_node'] = $auth_node;
        $top_button_option['options'] = $options;

        $this->_top_button_list[] = $top_button_option;
        return $this;
    }


    public function addSearchItem($name, $type, $title='', $options = array(), $auth_node = ''){
        $search_item = array(
            'name' => $name,
            'type' => $type,
            'title' => $title,
            'options' => $options,
            'auth_node' => $auth_node
        );

        $this->_search[] = $search_item;
        return $this;
    }



    /**
     * 加一个表格列标题字段
     *
     * @param string $name 列名
     * @param string $title 列标题
     * @param string $type 列类型，默认为null（目前支持类型：status、icon、date、time、picture、type、fun、a、self、num）
     * @param string|array $value 列value，默认为''，根据组件自定义该值
     * @param boolean $editable 列是否可编辑，默认为false
     * @param string $tip 列数据提示文字，默认为''
     * @param string $th_extra_attr 列表头额外属性，默认为''
     * @param string $td_extra_attr 列列额外属性，默认为''
     * @param string|array $auth_node 列权限点
     * @return $this
     */
    public function addTableColumn($name, $title, $type = null, $value = '', $editable = false, $tip = '',
                                   $th_extra_attr = '', $td_extra_attr = '', $auth_node = '') {

        $this->appendColumnName($name);

        $this->_table_column_list[] = self::genOneColumnOpt($name, $title, $type, $value, $editable, $tip,
            $th_extra_attr, $td_extra_attr, $auth_node);

        return $this;
    }

    /**
     * 表格数据列表
     */
    public function setTableDataList($table_data_list) {
        $this->_table_data_list = $table_data_list;
        $this->_origin_table_data_list = $table_data_list;
        return $this;
    }

    /**
     * 表格数据列表的主键名称
     */
    public function setTableDataListKey($table_data_list_key) {
        $this->_table_data_list_key = $table_data_list_key;
        return $this;
    }

    /**
     * 加入一个数据列表右侧按钮
     * 在使用预置的几种按钮时，比如我想改变编辑按钮的名称
     * 那么只需要$builder->addRightButton('edit', array('title' => '换个马甲'))
     * 如果想改变地址甚至新增一个属性用上面类似的定义方法
     * 因为添加右侧按钮的时候你并没有办法知道数据ID，于是我们采用__data_id__作为约定的标记
     * __data_id__会在display方法里自动替换成数据的真实ID
     * @param string $type 按钮类型，取值参考registerBaseRightButtonType
     * @param array|null  $attribute 按钮属性，一个定义标题/链接/CSS类名等的属性描述数组
     * @param string $tips 按钮提示
     * @param string|array $auth_node 按钮权限点
     * @param string|array|object $options 按钮options
     * @return $this
     */
    public function addRightButton($type, $attribute = null, $tips = '', $auth_node = '', $options = []) {
        $this->_right_button_list[] = $this->genOneButton($type, $attribute, $tips, $auth_node, $options);
        return $this;
    }

    /**
     * 设置分页
     * @param $page
     * @return $this
     */
    public function setTableDataPage($table_data_page) {
        $this->_table_data_page = $table_data_page;
        return $this;
    }

    /**
     * 修改列表数据
     * 有时候列表数据需要在最终输出前做一次小的修改
     * 比如管理员列表ID为1的超级管理员右侧编辑按钮不显示删除
     * @param $page
     * @return $this
     */
    public function alterTableData($condition, $alter_data) {
        $this->_alter_data_list[] = array(
            'condition' => $condition,
            'alter_data' => $alter_data
        );
        return $this;
    }

    protected function backupPk(){
        foreach($this->_table_data_list as &$vo){
            $vo[$this->_primary_key] = $vo[$this->_table_data_list_key];
        }
    }

    public function getPrimaryKey(){
        return $this->_primary_key;
    }

    public function getBtnDefClass(){
        return $this->_right_btn_def_class;
    }

    /**
     * @deprecated 已在v13版本删除， 请使用 build 代替
     * 显示页面
     */
    public function display($render=false,$charset='',$contentType='',$content='',$prefix='') {
        E("display method is delete,use build instead");
    }

    public function build($render=false){
        $this->backupPk();
        // 编译data_list中的值
        $this->_right_button_list = $this->checkAuthNode($this->_right_button_list);
        $this->_table_column_list = $this->checkAuthNode($this->_table_column_list);
        $this->_top_button_list = $this->checkAuthNode($this->_top_button_list);
        $this->_search = $this->checkAuthNode($this->_search);

        foreach ($this->_table_data_list as $key => &$data) {

            // 编译表格右侧按钮
            if ($this->_right_button_list) {
                $data['right_button'] = join(' ', $this->parseButtonList($this->_right_button_list, $data));
            }

            // 根据表格标题字段指定类型编译列表数据
            foreach ($this->_table_column_list as &$column) {
                $is_editable = $this->isEditable($column, $data);
                if($is_editable && !isset($data[$this->_hidden_key])){
                    $hidden = new \Qscmf\Builder\ColumnType\Hidden\Hidden();
                    $hidden_column = [
                        'name' => $this->_table_data_list_key
                    ];
                    $hidden_data = $data;
                    $hidden_data[$this->_table_data_list_key] = $data[$this->getPrimaryKey()];
                    $data[$this->_hidden_key] = $hidden->editBuild($hidden_column, $hidden_data, $this);
                }

                $this->buildOneColumnItem($column, $data);
            }

            $data['_check_box'] = $this->parseCheckBox($data);

            /**
             * 修改列表数据
             * 有时候列表数据需要在最终输出前做一次小的修改
             * 比如管理员列表ID为1的超级管理员右侧编辑按钮不显示删除
             */
            if ($this->_alter_data_list) {
                foreach ($this->_alter_data_list as $alter) {
                    if ($data[$alter['condition']['key']] === $alter['condition']['value']) {
                        //寻找alter_data里需要替代的变量
                        foreach($alter['alter_data'] as $alter_key => $val){
                            $val = $this->parseData($val, $this->_origin_table_data_list[$key]);
                            $alter['alter_data'][$alter_key] = $val;
                        }
                        $data = array_merge($data, $alter['alter_data']);
                    }
                }
            }

            foreach ($this->_table_column_list as &$column) {
                $data[$column['name']] = match ($column){
                    'right_button' => "<td nowrap {$column['td_extra_attr']}>{$data[$column['name']]}</td>",
                    default => "<td {$column['td_extra_attr']}>{$data[$column['name']]}</td>"
                };
            }
        }

        //编译top_button_list中的HTML属性
        if ($this->_top_button_list) {
            $top_button_list = [];
            foreach ($this->_top_button_list as $option) {
                $tmp = [];
                $content = (new $this->_top_button_type[$option['type']]())->build($option, $this);
                $button_html = self::compileTopButton($option);
                $tmp['render_content'] = <<<HTML
{$button_html}
{$content}
HTML;
                $top_button_list[] = $tmp;
            }
            $this->_top_button_list = $top_button_list;
        }

        if ($this->_meta_button_list) {
            foreach ($this->_meta_button_list as &$button) {
                $this->compileButton($button);
            }
        }

        foreach($this->_search as &$search){
            $search['render_content'] = (new $this->_search_type[$search['type']]())->build($search);
        }

        $this->assign('meta_title',          $this->_meta_title);          // 页面标题
        $this->assign('top_button_list',     $this->_top_button_list);     // 顶部工具栏按钮
        $this->assign('meta_button_list',     $this->_meta_button_list);
        $this->assign('search',              $this->_search);              // 搜索配置
        $this->assign('tab_nav',             $this->_tab_nav);             // 页面Tab导航
        $this->assign('table_column_list',   $this->_table_column_list);   // 表格的列
        $this->assign('table_data_list',     $this->_table_data_list);     // 表格数据
        $this->assign('table_data_list_key', $this->_table_data_list_key); // 表格数据主键字段名称
        $this->assign('pagination',     $this->_table_data_page);     // 表示个数据分页
        $this->assign('right_button_list',   $this->_right_button_list);   // 表格右侧操作按钮
        $this->assign('alter_data_list',     $this->_alter_data_list);     // 表格数据列表重新修改的项目
        $this->assign('extra_html',          $this->_extra_html);          // 额外HTML代码
        $this->assign('top_html',            $this->_top_html);            // 顶部自定义html代码
        $this->assign('page_template',       $this->_page_template);       // 页码模板自定义html代码
        $this->assign('show_check_box', $this->_show_check_box);
        $this->assign('hidden_key', $this->_hidden_key);
        $this->assign('nid', $this->_nid);
        $this->assign('gid', $this->_gid);
        $this->assign('lock_row', $this->_lock_row);
        $this->assign('lock_col', $this->_lock_col);
        $this->assign('lock_col_right', $this->_lock_col_right);
        $this->assign('search_url', $this->_search_url);
        $this->assign('list_builder_path', $this->_list_template);
        $this->assign('primary_key', $this->_primary_key);
        $this->assign('content_bottom_html', join('', $this->_content_bottom));
        $this->assign('column_css_and_js_str', $this->getUniqueColumnCssAndJs());

        if($render){
            return parent::fetch($this->_list_template);
        }
        else{
            parent::display($this->_template);
        }
    }

    protected function parseCheckBox($data) : string{
        if($this->_show_check_box){
            $attr = [
                'class' => 'ids',
                'type' => 'checkbox',
                'name' => 'ids[]',
                'value' => $data[$this->_primary_key]
            ];

            if($this->_attr_callback instanceof \Closure){
                $attr = call_user_func($this->_attr_callback, $attr, $data);
            }

            $attr_str = $this->compileHtmlAttr($attr);
            return "<td><input {$attr_str}></td>";
        }
        else{
            return "";
        }
    }

    protected function compileTopButton($option){
        if($option['tips'] != ''){
            $tips_html = '<span class="badge">' . $option['tips'] . '</span>';
        }

        return <<<HTML
<a {$this->compileHtmlAttr($option['attribute'])}>{$option['attribute']['title']} {$tips_html}</a>
HTML;
    }

    protected function compileButton(&$button){
        $button['attribute'] = $this->compileHtmlAttr($button);
        if($button['tips'] != ''){
            $button['tips'] = '<span class="badge">' . $button['tips'] . '</span>';
        }
    }

    protected function parseData($str, $data){
        while(preg_match('/__(\w+?)__/i', $str, $matches)){
            $str = str_replace('__' . $matches[1] . '__', $data[$matches[1]], $str);
        }
        return $str;
    }

    //编译HTML属性
    protected function compileHtmlAttr($attr) {
        $result = array();
        foreach ($attr as $key => $value) {
            if($key == 'tips'){
                continue;
            }

            if(!empty($value) && !is_array($value)){
                $value = htmlspecialchars($value);
                $result[] = "$key=\"$value\"";
            }
        }
        $result = implode(' ', $result);
        return $result;
    }


    /**
     * 设置页码模版
     * @param $page_template 页码模版自定义html代码
     * @return $this
     */
    public function setPageTemplate($page_template) {
        $this->_page_template = $page_template;
        return $this;
    }
}
