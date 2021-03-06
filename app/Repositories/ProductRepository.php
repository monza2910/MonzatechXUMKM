<?php

namespace App\Repositories;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Str;

class ProductRepository
{
    private $mediaCollection = 'photo';

    public function getProduct()
    {
        $products   = Product::with(['categories','brands','users','media'])->orderBy('id','desc')->get();
        return $products;
    }

    public function storeProduct($data){
        // dd($data);
        if (!empty($data['slug'])) {
            $slug   = Str::slug($data['slug']);
        } else {
            $slug   = Str::slug($data['name']);
        }

        $product = Product::create([
            'name'          => $data['name'],
            'slug'          => $slug,
            'description'   => $data['description'],
            'content'       => $data['content'],
            'status'        => $data['status'],
            'price'         => $data['price'],
            'qty'           => $data['qty'],
            'brand_id'      => $data['brand']
        ]);


        $product->categories()->attach($data['category']);
        foreach ($data['photo'] as $file) {
            $product->addMedia(storage_path('tmp/uploads/' . $file))->toMediaCollection($this->mediaCollection);
        }
    }

    public function getProductById($id)
    {
        $product    = Product::findOrFail($id);
        return $product;
    }

    public function updateProduct($id,$data)
    {


        if (!empty($data['slug'])) {
            $slug   = Str::slug($data['slug']);
        } else {
            $slug   = Str::slug($data['name']);
        }

        $product = Product::find($id);
        $product->update([
            'name'          => $data['name'],
            'slug'          => $slug,
            'description'   => $data['description'],
            'content'       => $data['content'],
            'status'        => $data['status'],
            'price'         => $data['price'],
            'qty'           => $data['qty'],
            'brand_id'      => $data['brand']
        ]);

        $product->categories()->sync($data['category']);

        if (count($product->photos) > 0) {
            foreach ($product->photos as $media) {
                if (!in_array($media->file_name, $data['photo'])) {
                    $media->delete();
                }
            }
        }

        $media = $product->photos->pluck('file_name')->toArray();

        foreach ($data['photo'] as $file) {
            if (count($media) === 0 || !in_array($file, $media)) {
                $product->addMedia(storage_path('tmp/uploads/' . $file))->toMediaCollection($this->mediaCollection);
            }
        }
    }

    public function softDeleteProduct($id)
    {
        $product    = Product::find($id);
        $product->delete();
    }

    public function trashProduct()
    {
        $products   = Product::onlyTrashed()->with(['categories','brands'])->orderBy('id','desc')->get();
        return $products;
    }

    public function restoreProduct($id)
    {
        $product    = Product::withTrashed()->find($id);
        $product->restore();
    }

    public function forceDeleteProduct($id)
    {
        $product   = Product::withTrashed()->find($id);
        $product->forceDelete();
    }
}
