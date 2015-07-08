<?php

namespace Admin\Model;
use Think\Model;
use Think\Upload;

class VersionModel extends Model{

    /**
     * 自动验证
     * @var array
     */
    protected $_validata = array(
        array('name','require', '必须输入版本名称', self::MUST_VALIDATE, 'regex', self::MODEL_INSERT),
        array('version', 'require', '必须输入版本号', self::MUST_VALIDATE, 'regex', self::MODEL_INSERT),
    );

    /**
     * 自动验证
     * @var array
     */
    protected $_auto = array(
        array('create_time', NOW_TIME, self::MODEL_INSERT),
        array('create_ip', 'get_client_ip', self::MODEL_INSERT, 'function', 1),
    );

    /**
     * 获取版本详细信息
     * @param  int $id 版本id
     * @param bool $field 查询字段
     * @return mixed
     */
    public function info($id, $field = true)
    {
        $map['id'] = $id;
        $version=  $this->field($field)->where($map)->find();
        return $version;
    }
    /**
     * 更新信息
     * @return boolean 更新状态
     */
    public function update()
    {

        $data = $this->create();
        if (!$data) { //数据对象创建错误
            return false;
        }
        /* 添加或更新数据 */
        if (empty($data['id'])) {

            $res = $this->add();

        } else {
            $res = $this->save();

        }
        return $res;
    }


    /**
     * 文件上传
     * @param  array  $files   要上传的文件列表（通常是$_FILES数组）
     * @param  array  $setting 文件上传配置
     * @param  string $driver  上传驱动名称
     * @param  array  $config  上传驱动配置
     * @return array           文件上传成功后的信息
     */
    public function upload($files, $setting, $driver = 'Local', $config = null){
        /* 上传文件 */
        $setting['callback'] = array($this, 'isFile');
        $setting['removeTrash'] = array($this, 'removeTrash');
        $Upload = new Upload($setting, $driver, $config);
        $info   = $Upload->upload($files);

        /* 设置文件保存位置 */
//        $this->_auto[] = array('location', 'ftp' === strtolower($driver) ? 1 : 0, self::MODEL_INSERT);

        if($info){ //文件上传成功，记录文件信息
            foreach ($info as $key => &$value) {
                /* 已经存在文件记录 */
//                if(isset($value['id']) && is_numeric($value['id'])){
//                    $value['path'] = substr($setting['rootPath'], 1).$value['savepath'].$value['savename']; //在模板里的url路径
//                    continue;
//                }

                $value['path'] = substr($setting['rootPath'], 1).$value['savepath'].$value['savename']; //在模板里的url路径
//                /* 记录文件信息 */
//                if($this->create($value) && ($id = $this->add())){
//                    $value['id'] = $id;
//                } else {
//                    //TODO: 文件上传成功，但是记录文件信息失败，需记录日志
//                    unset($info[$key]);
//                }
            }
            return $info; //文件上传成功
        } else {
            $this->error = $Upload->getError();
            return false;
        }
    }

    /**
     * 下载指定文件
     * @param  number  $root 文件存储根目录
     * @param  integer $id   文件ID
     * @param  string   $args     回调函数参数
     * @return boolean       false-下载失败，否则输出下载文件
     */
    public function download($root, $id, $callback = null, $args = null){
        /* 获取下载文件信息 */
        $file = $this->find($id);
        if(!$file){
            $this->error = '不存在该文件！';
            return false;
        }

        /* 下载文件 */
        switch ($file['location']) {
            case 0: //下载本地文件
                $file['rootpath'] = $root;
                return $this->downLocalFile($file, $callback, $args);
            case 1: //下载FTP文件
                $file['rootpath'] = $root;
                return $this->downFtpFile($file, $callback, $args);
                break;
            default:
                $this->error = '不支持的文件存储类型！';
                return false;

        }

    }

    /**
     * 检测当前上传的文件是否已经存在
     * @param  array   $file 文件上传数组
     * @return boolean       文件信息， false - 不存在该文件
     */
    public function isFile($file){
        if(empty($file['md5'])){
            throw new \Exception('缺少参数:md5');
        }
        /* 查找文件 */
        $map = array('md5' => $file['md5'],'sha1'=>$file['sha1'],);
        return $this->field(true)->where($map)->find();
    }

    /**
     * 下载本地文件
     * @param  array    $file     文件信息数组
     * @param  callable $callback 下载回调函数，一般用于增加下载次数
     * @param  string   $args     回调函数参数
     * @return boolean            下载失败返回false
     */
    private function downLocalFile($file, $callback = null, $args = null){
        if(is_file($file['rootpath'].$file['savepath'].$file['savename'])){
            /* 调用回调函数新增下载数 */
            is_callable($callback) && call_user_func($callback, $args);

            /* 执行下载 */ //TODO: 大文件断点续传
            header("Content-Description: File Transfer");
            header('Content-type: ' . $file['type']);
            header('Content-Length:' . $file['size']);
            if (preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])) { //for IE
                header('Content-Disposition: attachment; filename="' . rawurlencode($file['name']) . '"');
            } else {
                header('Content-Disposition: attachment; filename="' . $file['name'] . '"');
            }
            readfile($file['rootpath'].$file['savepath'].$file['savename']);
            exit;
        } else {
            $this->error = '文件已被删除！';
            return false;
        }
    }

    /**
     * 下载ftp文件
     * @param  array    $file     文件信息数组
     * @param  callable $callback 下载回调函数，一般用于增加下载次数
     * @param  string   $args     回调函数参数
     * @return boolean            下载失败返回false
     */
    private function downFtpFile($file, $callback = null, $args = null){
        /* 调用回调函数新增下载数 */
        is_callable($callback) && call_user_func($callback, $args);

        $host = C('DOWNLOAD_HOST.host');
        $root = explode('/', $file['rootpath']);
        $file['savepath'] = $root[3].'/'.$file['savepath'];

        $data = array($file['savepath'], $file['savename'], $file['name'], $file['mime']);
        $data = json_encode($data);
        $key = think_encrypt($data, C('DATA_AUTH_KEY'), 600);

        header("Location:http://{$host}/onethink.php?key={$key}");
    }

    /**
     * 清除数据库存在但本地不存在的数据
     * @param $data
     */
    public function removeTrash($data){
        $this->where(array('id'=>$data['id'],))->delete();
    }

}