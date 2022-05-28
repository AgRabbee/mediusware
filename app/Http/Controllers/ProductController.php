<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use Illuminate\Http\Request;
use DateTime;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index()
    {
        $db_data = ProductVariantPrice::leftJoin('products as p','p.id','product_variant_prices.product_id')
            ->leftJoin('product_variants as clr','clr.id','product_variant_prices.product_variant_one')
            ->leftJoin('product_variants as size','size.id','product_variant_prices.product_variant_two')
            ->orderBy('p.id', 'DESC')
            ->get([
                'p.id as product_id',
                'p.title as product_name',
                'p.description as product_desc',
                'p.created_at as product_created_at',
                'clr.variant as product_colour',
                'size.variant as product_size',
                'product_variant_prices.price as price',
                'product_variant_prices.stock as stock',
            ])->toArray();
        $dataArr = $this->prprData($db_data);

        $productVariant['color_variant'] = ProductVariant::where('variant_id',1)->groupBy('variant')->pluck('variant','variant');
        $productVariant['size_variant'] = ProductVariant::where('variant_id',2)->groupBy('variant')->pluck('variant','variant');
        return view('products.index', compact('dataArr','productVariant'));

    }
    public function prprData($givenData){
        $dataArr = [];
        foreach($givenData as $key=>$value){
            $dataArr[$value['product_id']]['sl']=$value['product_id'];
            $dataArr[$value['product_id']]['title']=$value['product_name'];
            $dataArr[$value['product_id']]['time_age']=$this->time_elapsed_string($value['product_created_at'], true);
            $dataArr[$value['product_id']]['desc']=$value['product_desc'];

            $dataArr[$value['product_id']]['variant'][$key]['color_size']=$value['product_colour'].' / '.$value['product_size'];
            $dataArr[$value['product_id']]['variant'][$key]['price']=$value['price'];
            $dataArr[$value['product_id']]['variant'][$key]['stock']=$value['stock'];
        }
        $perpage = 2;
        $currentURL = url()->current();
        return $this->paginate($dataArr,$perpage,null,$currentURL);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function create()
    {
        $variants = Variant::all();
        return view('products.create', compact('variants'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $productObj = Product::firstOrNew(['id'=>$request['product_id']]);

            $productObj->title = $request['title'];
            $productObj->sku = $request['sku'];
            $productObj->description = $request['description'];
            if(empty($request['product_id'])){
                $productObj->created_at = date('Y-m-d H:i:s');
            }
            $productObj->updated_at = date('Y-m-d H:i:s');
            $productObj->save();

            // image store
            if(count($request['product_image']) > 0){
                // check already exist during update
                $existingImage = ProductImage::where('product_id',$productObj->id)->get();
                if(count($existingImage) > 0){
                    $existingImage->each->delete();
                }
                foreach ($request['product_image'] as $item) {
                    $productImageObj = new ProductImage();
                    $productImageObj->product_id = $productObj->id;
                    $productImageObj->file_path = $item;
                    $productImageObj->created_at = date('Y-m-d H:i:s');
                    $productImageObj->updated_at = date('Y-m-d H:i:s');
                    $productImageObj->save();
                }
            }

            // store in product_variant table
            if(count($request['product_variant']) > 0){
                // check already exist during update
                $existingVariant = ProductVariant::where('product_id',$productObj->id)->get();
                if(count($existingVariant) > 0){
                    $existingVariant->each->delete();
                }

                foreach($request['product_variant'] as $key=>$p_variant){
                    foreach($p_variant['tags'] as $tag_index=> $tag){
                        $productVariant = new ProductVariant();
                        $productVariant->variant = trim($tag);
                        $productVariant->variant_id = $p_variant['option'];
                        $productVariant->product_id = $productObj->id;
                        $productVariant->created_at = date('Y-m-d H:i:s');
                        $productVariant->updated_at = date('Y-m-d H:i:s');
                        $productVariant->save();
                    }
                }
            }

            // store in product_variant_prices table
            if(count($request['product_variant_prices']) > 0){
                // check already exist during update
                $existingVariantPrices = ProductVariantPrice::where('product_id',$productObj->id)->get();
                if(count($existingVariantPrices) > 0){
                    $existingVariantPrices->each->delete();
                }

                foreach($request['product_variant_prices'] as $pvp_index => $pvp){
                    $splittedPVP = explode('/',$pvp['title']);
                    $pVariantArr = ProductVariant::where(['product_id'=>$productObj->id])
                        ->whereIn('variant',$splittedPVP )
                        ->pluck('id')->toArray();

                    $ProductVariantPriceObj = new ProductVariantPrice();
                    $ProductVariantPriceObj->product_variant_one = !empty($pVariantArr[0]) ? $pVariantArr[0] : null;
                    $ProductVariantPriceObj->product_variant_two = !empty($pVariantArr[1]) ? $pVariantArr[1] : null;
                    $ProductVariantPriceObj->product_variant_three = !empty($pVariantArr[2]) ? $pVariantArr[2] : null;
                    $ProductVariantPriceObj->price = $pvp['price'];
                    $ProductVariantPriceObj->stock = $pvp['stock'];
                    $ProductVariantPriceObj->product_id = $productObj->id;
                    $ProductVariantPriceObj->created_at = date('Y-m-d H:i:s');
                    $ProductVariantPriceObj->updated_at = date('Y-m-d H:i:s');
                    $ProductVariantPriceObj->save();
                }
            }

            DB::commit();
            if(!empty($request['product_id'])){
                return ['responseCode' => 1,'msg'=>'Product successfully updated'];
            }else{
                return ['responseCode' => 1,'msg'=>'Product successfully created'];
            }
        }
        catch (\Exception $e){
            DB::rollBack();
            return ['responseCode' => -1,'msg'=>'Product cannot be created or updated'];
        }

    }


    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function show($product)
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        $productVariantDbData = ProductVariant::where('product_id',$product->id)->get()->toArray();
        $productVariants = [];
        foreach($productVariantDbData as $key=>$datum){
            $index = array_search($datum['variant_id'], array_column($productVariantDbData, 'variant_id'));
            $productVariants[$index]['option'] = $datum['variant_id'];
            $productVariants[$index]['tags'][] = $datum['variant'];
        }

        $productVariantPrices = ProductVariantPrice::leftJoin('product_variants as one','one.id','product_variant_prices.product_variant_one')
                                ->leftJoin('product_variants as two','two.id','product_variant_prices.product_variant_two')
                                ->leftJoin('product_variants as three','three.id','product_variant_prices.product_variant_three')
                                ->where('product_variant_prices.product_id',$product->id)
                                ->get([
                                    'one.variant as clr',
                                    'two.variant as size',
                                    'three.variant as style',
                                    'product_variant_prices.price',
                                    'product_variant_prices.stock'
                                ])->toArray();

        foreach($productVariantPrices as $index => $variant){
            $title = '';
            if(!empty($variant['clr'])){
                $title = $title.$variant['clr'].'/';
            }
            if(!empty($variant['size'])){
                $title = $title.$variant['size'].'/';
            }
            if(!empty($variant['style'])){
                $title = $title.$variant['style'];
            }
            $productVariantPrices[$index]['title'] = $title;
            unset($productVariantPrices[$index]['clr']);
            unset($productVariantPrices[$index]['size']);
            unset($productVariantPrices[$index]['style']);

        }

        $productdata = [];
        $productdata['product'] = $product;
        $productdata['productVariants'] = $productVariants;
        $productdata['productVariantPrices'] = $productVariantPrices;
        $productdata = json_encode($productdata);

        $variants = Variant::all();
        return view('products.edit', compact('variants','productdata'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }

    public function productFilter(Request $request)
    {
        $srch_title = $request['title'];
        $srch_variant = $request['variant'];
        $srch_price_from = $request['price_from'];
        $srch_price_to = $request['price_to'];
        $srch_date = $request['date'];

        $db_data = ProductVariantPrice::leftJoin('products as p','p.id','product_variant_prices.product_id')
            ->leftJoin('product_variants as clr','clr.id','product_variant_prices.product_variant_one')
            ->leftJoin('product_variants as size','size.id','product_variant_prices.product_variant_two');

        if(!empty($srch_title)){
            $db_data = $db_data->where('p.title','like', '%' . $srch_title . '%');
        }
        if(!empty($srch_variant)){
            $db_data = $db_data->where('clr.variant', $srch_variant);
        }
        if(!empty($srch_price_from)){
            $db_data = $db_data->where('product_variant_prices.price','>=',$srch_price_from);
        }
        if(!empty($srch_price_to)){
            $db_data = $db_data->where('product_variant_prices.price','<=',$srch_price_to);
        }
        if(!empty($srch_date)){
            $db_data = $db_data->whereDate('product_variant_prices.created_at',$srch_date);
        }

        $db_data = $db_data->get([
                'p.id as product_id',
                'p.title as product_name',
                'p.description as product_desc',
                'p.created_at as product_created_at',
                'clr.variant as product_colour',
                'size.variant as product_size',
                'product_variant_prices.price as price',
                'product_variant_prices.stock as stock',
            ])->toArray();
        $dataArr = $this->prprData($db_data);

        $productVariant = [];
        $productVariant['color_variant'] = ProductVariant::where('variant_id',1)->groupBy('variant')->pluck('variant','variant');
        $productVariant['size_variant'] = ProductVariant::where('variant_id',2)->groupBy('variant')->pluck('variant','variant');
        return view('products.index', compact('dataArr','productVariant','srch_variant','srch_title','srch_variant','srch_price_from','srch_price_to','srch_date'));
    }

    public function time_elapsed_string($datetime, $full = false) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }

    public function paginate($items, $perPage = 4, $page = null,$currentURL)
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $total = count($items);
        $currentpage = $page;
        $offset = ($currentpage * $perPage) - $perPage ;
        $itemstoshow = array_slice($items , $offset , $perPage);
        return new LengthAwarePaginator($itemstoshow ,$total ,$perPage,$page,['path'=>$currentURL]);
    }

    public function storeImage(Request $request){
        if($request->file('file')){

            $file = $request->file('file');
            $ext = $file->getClientOriginalExtension();
            $fileName = uniqid(rand(), true).'.'.$ext;

            $path = $file->storeAs('uploads', $fileName);
            if($path){
                return response()->json(['msg'=>'file uploaded.', 'path'=>$path]);
            }
            return response()->json(['msg'=>'file cannot be uploaded.', 'path'=>'']);

        }
    }
}
