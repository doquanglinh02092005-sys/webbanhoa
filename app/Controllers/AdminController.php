<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use mysqli_sql_exception;
use RuntimeException;

final class AdminController extends Controller
{
    private array $admin;
    private Product $products;
    private Category $categories;
    private Order $orders;
    private User $users;

    public function __construct()
    {
        $this->admin = require_admin();
        $this->products = new Product();
        $this->categories = new Category();
        $this->orders = new Order();
        $this->users = new User();
    }

    private function adminView(string $file, array $data): void
    {
        extract($data, EXTR_OVERWRITE);
        $renderAdminView = true;
        require dirname(__DIR__, 2) . "/admin/" . $file . ".php";
    }

    public function dashboard(): void
    {
        $stats=['products'=>$this->products->countManaged(),'low_stock'=>$this->products->countLowStock(),'orders'=>$this->orders->countAll(),'customers'=>$this->users->countCustomers(),'revenue'=>$this->orders->revenue()];
        $this->adminView('index',['admin'=>$this->admin,'pageTitle'=>'Tổng quan','activeAdminPage'=>'dashboard','flashMessage'=>pull_flash(),'stats'=>$stats,'recentOrders'=>$this->orders->recent(),'lowStockProducts'=>$this->products->lowStock(),'recentCustomers'=>$this->users->recentCustomers()]);
    }

    public function products(): void
    {
        if($_SERVER['REQUEST_METHOD']==='POST'){
            verify_csrf();$id=(int)($_POST['id']??0);$action=(string)($_POST['action']??'');
            if($id>0&&$action==='delete'){$this->products->delete($id);flash('success','Đã xóa sản phẩm.');}
            elseif($id>0&&$action==='toggle'){$status=($_POST['status']??'')==='active'?'active':'hidden';$this->products->setStatus($id,$status);flash('success',$status==='active'?'Đã hiện sản phẩm.':'Đã ẩn sản phẩm.');}
            redirect('products.php');
        }
        $q=trim((string)($_GET['q']??''));$status=(string)($_GET['status']??'');$categoryId=(int)($_GET['category_id']??0);$lowStock=($_GET['stock']??'')==='low';
        $this->adminView('products',['admin'=>$this->admin,'pageTitle'=>'Sản phẩm','activeAdminPage'=>'products','flashMessage'=>pull_flash(),'q'=>$q,'status'=>$status,'categoryId'=>$categoryId,'products'=>$this->products->filtered($q,$status,$categoryId,$lowStock),'categories'=>$this->categories->all()]);
    }

    public function productForm(): void
    {
        $id=(int)($_GET['id']??$_POST['id']??0);$product=$id>0?$this->products->find($id):null;
        if($id>0&&!$product){http_response_code(404);exit('Sản phẩm không tồn tại.');}
        $errors=[];$data=[
            'name'=>(string)($_POST['name']??$product['name']??''),'sku'=>(string)($_POST['sku']??$product['sku']??''),'category_id'=>(int)($_POST['category_id']??$product['category_id']??0),'color'=>(string)($_POST['color']??$product['color']??''),'occasion'=>(string)($_POST['occasion']??$product['occasion']??''),
            'price'=>(int)($_POST['price']??$product['price']??0),'compare_price'=>(int)($_POST['compare_price']??$product['compare_price']??0),'stock_quantity'=>(int)($_POST['stock_quantity']??$product['stock_quantity']??0),'badge'=>(string)($_POST['badge']??$product['badge']??''),
            'image_url'=>(string)($_POST['image_url']??$product['image_url']??''),'short_description'=>(string)($_POST['short_description']??$product['short_description']??''),'description'=>(string)($_POST['description']??$product['description']??''),'status'=>(string)($_POST['status']??$product['status']??'active'),'featured'=>isset($_POST['featured'])?1:(($_SERVER['REQUEST_METHOD']??'')==='POST'?0:(int)($product['featured']??0)),
        ];
        if($_SERVER['REQUEST_METHOD']==='POST'){
            verify_csrf();$data['name']=trim($data['name']);$data['sku']=strtoupper(trim($data['sku']));$data['color']=trim($data['color']);$data['occasion']=strtolower(trim($data['occasion']));$data['badge']=trim($data['badge']);$data['image_url']=trim($data['image_url']);
            if(!in_array($data['occasion'],['birthday','love','congratulations','bouquet','basket','wedding','seasonal'],true))$data['occasion']='';
            if(mb_strlen($data['name'])<2)$errors[]='Tên sản phẩm phải có ít nhất 2 ký tự.';if($data['sku']==='')$errors[]='Mã sản phẩm không được để trống.';if($data['price']<=0)$errors[]='Giá bán phải lớn hơn 0.';if($data['compare_price']>0&&$data['compare_price']<$data['price'])$errors[]='Giá gốc phải lớn hơn hoặc bằng giá bán.';if($data['stock_quantity']<0)$errors[]='Tồn kho không hợp lệ.';if(!in_array($data['status'],['active','hidden','draft'],true))$data['status']='draft';
            try{$data['image_url']=admin_upload_product_image($_FILES['image']??[],$data['image_url']?:null)??'';}catch(RuntimeException $e){$errors[]=$e->getMessage();}
            if(!$errors){try{$this->products->save($data,$id);flash('success',$id>0?'Đã cập nhật sản phẩm.':'Đã thêm sản phẩm mới.');redirect('products.php');}catch(mysqli_sql_exception $e){$errors[]=$e->getCode()===1062?'Mã sản phẩm đã tồn tại.':'Không thể lưu sản phẩm.';}}
        }
        $preview=$data['image_url'];if($preview&&!preg_match('#^https?://#',$preview))$preview='../'.ltrim($preview,'/');
        $this->adminView('product-form',['admin'=>$this->admin,'pageTitle'=>$product?'Sửa sản phẩm':'Thêm sản phẩm','activeAdminPage'=>'products','flashMessage'=>pull_flash(),'id'=>$id,'product'=>$product,'errors'=>$errors,'data'=>$data,'preview'=>$preview,'categories'=>$this->categories->all(true)]);
    }

    public function categories(): void
    {
        $errors=[];$editId=(int)($_GET['edit']??$_POST['id']??0);$editCategory=$editId>0?$this->categories->find($editId):null;
        if($_SERVER['REQUEST_METHOD']==='POST'){
            verify_csrf();$action=(string)($_POST['action']??'save');
            if($action==='delete'){try{$this->categories->delete((int)($_POST['id']??0));flash('success','Đã xóa danh mục.');}catch(RuntimeException $e){flash('error',$e->getMessage());}redirect('categories.php');}
            $data=['name'=>trim((string)($_POST['name']??'')),'description'=>trim((string)($_POST['description']??'')),'status'=>($_POST['status']??'')==='hidden'?'hidden':'active','sort_order'=>(int)($_POST['sort_order']??0)];
            if(mb_strlen($data['name'])<2)$errors[]='Tên danh mục phải có ít nhất 2 ký tự.';
            if(!$errors){$this->categories->save($data,$editId);flash('success',$editId?'Đã cập nhật danh mục.':'Đã thêm danh mục.');redirect('categories.php');}
        }
        $this->adminView('categories',['admin'=>$this->admin,'pageTitle'=>'Danh mục','activeAdminPage'=>'categories','flashMessage'=>pull_flash(),'errors'=>$errors,'editId'=>$editId,'editCategory'=>$editCategory,'categories'=>$this->categories->all()]);
    }

    public function orders(): void
    {
        if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();$id=(int)($_POST['id']??0);$status=(string)($_POST['status']??'');if($id>0&&in_array($status,['pending','confirmed','preparing','shipping','completed','cancelled'],true)){try{$this->orders->update($id,$status);flash('success','Đã cập nhật trạng thái đơn hàng.');}catch(RuntimeException $e){flash('error',$e->getMessage());}}redirect('orders.php');}
        $q=trim((string)($_GET['q']??''));$status=(string)($_GET['status']??'');
        $this->adminView('orders',['admin'=>$this->admin,'pageTitle'=>'Đơn hàng','activeAdminPage'=>'orders','flashMessage'=>pull_flash(),'q'=>$q,'status'=>$status,'orders'=>$this->orders->filtered($q,$status)]);
    }

    public function order(): void
    {
        $id=(int)($_GET['id']??$_POST['id']??0);
        if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();$status=(string)($_POST['status']??'');$payment=(string)($_POST['payment_status']??'');if(in_array($status,['pending','confirmed','preparing','shipping','completed','cancelled'],true)&&in_array($payment,['unpaid','paid','refunded'],true)){try{$this->orders->update($id,$status,$payment);flash('success','Đã cập nhật đơn hàng và điểm thưởng.');}catch(RuntimeException $e){flash('error',$e->getMessage());}}redirect('order.php?id='.$id);}
        $order=$this->orders->find($id);if(!$order){http_response_code(404);exit('Đơn hàng không tồn tại.');}
        $this->adminView('order',['admin'=>$this->admin,'pageTitle'=>'Chi tiết đơn hàng','activeAdminPage'=>'orders','flashMessage'=>pull_flash(),'id'=>$id,'order'=>$order,'items'=>$this->orders->items($id)]);
    }

    public function customers(): void
    {
        if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();$id=(int)($_POST['user_id']??0);$action=(string)($_POST['action']??'');if($id===(int)$this->admin['id'])flash('error','Không thể thay đổi tài khoản đang đăng nhập.');elseif($id>0&&$action==='role'){$role=(string)($_POST['role']??'customer');if(in_array($role,['admin','customer'],true)){$this->users->updateRole($id,$role);flash('success','Đã cập nhật quyền tài khoản.');}}elseif($id>0&&$action==='status'){$status=(string)($_POST['status']??'active');if(in_array($status,['active','disabled'],true)){$this->users->updateStatus($id,$status);flash('success',$status==='active'?'Đã mở khóa tài khoản.':'Đã khóa tài khoản.');}}redirect('customers.php');}
        $q=trim((string)($_GET['q']??''));
        $this->adminView('customers',['admin'=>$this->admin,'pageTitle'=>'Khách hàng','activeAdminPage'=>'customers','flashMessage'=>pull_flash(),'q'=>$q,'users'=>$this->users->allWithOrderStats($q)]);
    }
}
