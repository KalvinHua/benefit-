<?php
namespace app\admin\controller;

use think\Controller;
use \app\admin\validate\Category as CategoryValidate;
use \app\common\model\Category as CategoryModel;
use think\response;

class Category extends Controller
{
    /**
     * @var city 模型对象  validate 验证器对象
     */
    private $category;
    private $validate;

    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        $this->category = new CategoryModel;
        $this->validate = new CategoryValidate;
    }

    /**
     * 获取并显示表中的分类 页面
     * parent_id为0为一级分类页面
     * paren_id其他为其对应的子分类页面
     *
     * @return mixed
     */
    public function index()
    {
        //获取parent_id = id 用于获取父节点为id的节点
        $parent_id = $this->request->param('parent_id', 0, 'int');
        $categories = $this->category->getFirstCategory($parent_id);
        $this->assign([
            'categories'=>$categories,
            'parentId'=>$parent_id,
        ]);
        return $this->fetch();
    }

    /**
     * 添加分类页面
     *
     * @return mixed
     */
    public function add()
    {
        $parent_id = $this->request->param('parent_id', 0, 'int');
        $categories = $this->category->getFirstCategory($parent_id, true);
        $this->assign(['categories'=> $categories]);
        return $this->fetch();
    }

    /**
     * 添加分类 保存
     *
     * @return mixed
     */
    public function save()
    {
        $data = $this->request->only(['name', 'parent_id']);

        if (!$this->validate->scene('add')->check($data)) {
            $this->error($this->validate->getError());
        } else {
            $this->category->addCategory($data);
            $this->success($data['name'] . '服务分类添加成功');
        }
    }

    /**
     * 获取修改分类页面
     *
     * @param $id 数据id
     * @return mixed
     */
    public function edit($id)
    {
        if (intval($id) < 1) {
            $this->error('参数不合法');
        } else {
            $categeory = $this->category->get($id);
            $categories = $this->category->getFirstCategory(0, true);
            $this->assign(['category'=>$categeory,
                'categories'=>$categories
                ]);
        }
        return $this->fetch();
    }

    /**
     * 更新分类信息 name 名称 parent_id 父级分类
     *
     * @param int $id
     * @return mixed
     */
    public function update($id)
    {
        $category = $this->category->get($id);
        //当参数为1个时放回的为字符串 多个时为返回关联数组
        $data = $this->request->param();
        //验证传入的必须为关联数组
        if (!$this->validate->scene('update')->check($data)) {
            $this->error($this->validate->getError());
        } else {
            // 只允许更新指定的字段数据
            $category->allowField(['name', 'parent_id'])
                ->save($data);
            $this->success('分类[' . $category->name . ']更新成功');
        }
    }



    /**
     * 更改状态 status
     * status=0/1 待审查/正常
     * status=-1  已删除
     */
    public function status()
    {
        $data = $this->request->only(['id', 'status']);
        if (!$this->validate->scene('status')) {
            $this->error($this->validate->getError());
        } else {
            //当不为删除时
            if ($data['status'] != -1) {
                $res = $this->category->save(['status' => $data['status']], ['id' => $data['id']]);
                if ($res) {
                    $this->success('状态更新成功');
                } else {
                    $this->success('状态更新失败');
                }
            } else {  //当删除时
                //判断是否存在子分类
                //返回的为多维数组
                $results = $this->category->where('parent_id', $data['id'])->select();
                if (count($results) > 0) {
                    $this->category->save(['status' => $data['status']], ['id' => $data['id']]);
                    foreach ($results as $key => $result) {
                        $this->category->update(['status' => $data['status'], 'id' => $result->id]);
                    }
                    $this->success('删除成功');
                } else {
                    $res = $this->category->save(['status' => $data['status']], ['id' => $data['id']]);
                    if ($res) {
                        $this->success('删除成功');
                    } else {
                        $this->success('删除失败');
                    }
                }
            }
        }
    }
}
