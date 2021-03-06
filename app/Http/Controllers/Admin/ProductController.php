<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Repositories\Interfaces\BillRepositoryInterface;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use App\Repositories\Interfaces\BrandRepositoryInterface;
use App\Repositories\Interfaces\ColorRepositoryInterface;
use App\Repositories\Interfaces\SpecificationRepositoryInterface;
use App\Repositories\Interfaces\CommentRepositoryInterface;

class ProductController extends Controller
{
    private $product;
    private $category;
    private $brand;
    private $color;
    private $comment;
    private $specification;
    private $bill;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        CategoryRepositoryInterface $categoryRepository,
        ColorRepositoryInterface $colorRepository,
        SpecificationRepositoryInterface $specificationRepository,
        CommentRepositoryInterface $commentRepository,
        BrandRepositoryInterface $brandRepository,
        BillRepositoryInterface  $billRepository
    ) {
        $this->product = $productRepository;
        $this->category = $categoryRepository;
        $this->brand = $brandRepository;
        $this->color = $colorRepository;
        $this->specification = $specificationRepository;
        $this->comment = $commentRepository;
        $this->bill = $billRepository;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        
        // $view = ['listNameProduct' => $this->product->getNameAllProduct()];
        ////get only name product
        $view['listNameProduct'] = $this->product->getNameAllProduct(); //Auto-Complete
        ////lấy ds sản phẩm 
        $view['listProduct'] = $this->product->listProduct($request->all(), $request->url(), $request->all());
        ////lấy danh mục cấp 2
        $view['listCategory'] = $this->category->getListCategoryNotParent();
        /////lấy danh sách thương hiệu
        $view['listBrand'] = $this->brand->getListBrand();
        
        // dd($view['listProduct']);
        return view('admin.product.list', $view);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required',
            'status' => 'required',
            'category' => 'required',
            'brand' => 'required'
        ];

        $messages = [
            'name.required' => 'Tên là bắt buộc.',
            'status.required' => 'Tình trạng là bắt buộc.',
            'category.required' => 'Danh mục là bắt buộc.',
            'brand.required' => 'Thương hiệu là bắt buộc.'
        ];

        $validator = \Validator::make($request->all(), $rules, $messages);

        $array = [];

        if ($validator->fails()) {

            $data_errors = $validator->errors();

            foreach ($data_errors->messages() as $key => $error) {

                $array[] = ['key' => $key, 'mess' => $error];
            }

            return $this->dataError('Errors', $array);
        } else {

            $checkValidation = true;

            if ($request->hasFile('image')) {
                //File Validation
                $exeFile = $request->image->getClientOriginalExtension();

                switch ($exeFile) {
                    case 'JPG':
                        break;
                    case 'jpg':
                        break;
                    case 'JPEG':
                        break;
                    case 'jpeg':
                        break;
                    case 'PNG':
                        break;
                    case 'png':
                        break;
                    case 'BMP':
                        break;
                    case 'bmp':
                        break;
                    case 'GIF':
                        break;
                    case 'gif':
                        break;
                    default:
                        $checkValidation = false;
                        $array[] = ['key' => 'image', 'mess' => ["Định dạng phải là kiểu hình ảnh."]];
                        break;
                }
            }
            // dd($this->product->checkUniqueName($request->name, null));
            if (!$this->product->checkUniqueName($request->name, null)) {
                $array[] = ['key' => 'name', 'mess' => ["Xin chọn tên khác. Tên này đã tồn tại."]];

                $checkValidation = false;
            }

            if (!$checkValidation) {

                return $this->dataError('Errors', $array);
            }

            $data['name'] = $request->name;
            $data['category_id'] = $request->category;
            $data['brand_id'] = $request->brand;
            $data['status'] = $request->status;
            $data['description'] = $request->description;

            if ($request->hasFile('image')) {
                $imageFile = $request->file('image');

                $imageName = 'upload/' . time() . str_replace(" ", "", $imageFile->getClientOriginalName());;

                $imageFile->move(public_path('upload/'), $imageName);

                $data['image'] = $imageName;
            }

            $saveProduct = $this->product->save($data);

            if ($saveProduct) {

                $dataSpecification['product_id'] = $saveProduct->id;

                $saveSpecification = $this->specification->save($dataSpecification);

                if ($saveSpecification) {

                    return $this->dataSuccess('Thêm sản phẩm thành công!');
                } else {

                    return $this->dataSuccess('Thêm sản phẩm thành công nhưng bị lỗi lưu thông số!');
                }
            }
        }
        return $this->dataError('Thất bại!', $array);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($product_id)
    {
        $view['product'] = $this->product->showProduct($product_id);

        $view['listColor'] = $this->color->getListColor();
        // dd($view['listColor']);
        return view('admin.product-detail.list', $view);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'name' => 'required',
            'status' => 'required',
            'category' => 'required',
            'brand' => 'required'

        ], [
            'name.required' => 'Tên là bắt buộc.',
            'status.required' => 'Tình trạng là bắt buộc.',
            'category.required' => 'Danh mục là bắt buộc.',
            'brand.required' => 'Thương hiệu là bắt buộc.'
        ]);

        $array = [];

        if ($validator->fails()) {

            $data_errors = $validator->errors();

            foreach ($data_errors->messages() as $key => $error) {

                $array[] = ['key' => $key, 'mess' => $error];
            }

            return $this->dataError('Errors', $array);
        } else {

            $checkValidation = true;

            if ($request->hasFile('image')) {
                //File Validation
                $exeFile = $request->image->getClientOriginalExtension();

                switch ($exeFile) {
                    case 'JPG':
                        break;
                    case 'jpg':
                        break;
                    case 'JPEG':
                        break;
                    case 'jpeg':
                        break;
                    case 'PNG':
                        break;
                    case 'png':
                        break;
                    case 'BMP':
                        break;
                    case 'bmp':
                        break;
                    case 'GIF':
                        break;
                    case 'gif':
                        break;
                    default:
                        $checkValidation = false;
                        $array[] = ['key' => 'image', 'mess' => ["Định dạng phải là kiểu hình ảnh."]];
                        break;
                }
            }
            
            if (!$this->product->checkUniqueName($request->name, $request->product_id)) {
                $array[] = ['key' => 'name', 'mess' => ["Xin chọn tên khác. Tên này đã tồn tại."]];

                $checkValidation = false;
            }

            if (!$checkValidation) {

                return $this->dataError('Errors', $array);
            }

            $data['name'] = $request->name;
            $data['category_id'] = $request->category;
            $data['brand_id'] = $request->brand;
            $data['status'] = $request->status;
            $data['description'] = $request->description;

            if ($request->hasFile('image')) {
                $imageFile = $request->file('image');

                $imageName = 'upload/' . time() . str_replace(" ", "", $imageFile->getClientOriginalName());

                $imageFile->move(public_path('upload/'), $imageName);

                $data['image'] = $imageName;
            }

            $saveProduct = $this->product->update($data, $request->product_id);

            if ($saveProduct) {

                return $this->dataSuccess('Chỉn sửa sản phẩm thành công!');
            }
        }
        return $this->dataError('Thất bại!', $array);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function destroyMulti(Request $request)
    {
        // $data["list_id"] = ['153328355727647','153328356657098'];
        //$data = ['list_id'=>$req->arrid];
        $data["list_id"] = $request->arrid;
        
        $checkBill = $this->bill->getProductBill($data["list_id"]);
        
        if (!$checkBill) return $this->dataError('Sản phẩm hiện đang có người đặt, không thể xoá');

        $deleteMulti = $this->product->deleteMulti($data);
        $deleteComments = $this->comment->deleteComments($data["list_id"]);


        $deleteSpecfication = $this->specification->deleteSpecfication($data["list_id"]);

        if ($deleteMulti == true && $deleteComments == true && $deleteSpecfication == true) {

            return $this->dataSuccess('Xóa sản phẩm thành công!');
        } else {

            return $this->dataError('Thất bại!');
        }
    }
}
