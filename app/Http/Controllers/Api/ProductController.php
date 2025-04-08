<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Helpers\UploadImage;
use App\Models\Product;
use App\Models\CatProMap;
use App\Models\ProductPhoto;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{

    public function index()
    {
        $products = Product::with([
            'photos:id,pro_id,image,thumbnail,seq,active',
            'categories:id,title,parent_id',
            'categories.parent:id,title'
        ])->get();
        $products->transform(function ($product) {
            $product->photos->transform(function ($photo) {
                $photo->image = getImageUrl($photo->image);
                $photo->thumbnail = getImageUrl($photo->thumbnail);
                return $photo;
            });
            return $product;
        });

        return response()->json($products, 200);
    }




    public function store(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required|string',
                'code' => 'nullable|string|unique:products,code',
                'sku' => 'nullable|string|unique:products,sku',
                'tax' => 'nullable|numeric',
                'slug' => 'nullable|string|unique:products,slug',
                'description' => 'nullable|string',
                'seq' => 'nullable|integer',
                'cat_id' => 'required|array',
                'cat_id.*' => 'exists:categories,id',
                'images' => 'nullable|array',
                'images.*' => 'image|mimes:jpeg,png,jpg,svg,webp,avif|max:2048',
            ]);

            $product = Product::create([
                'title' => $request->title,
                'code' => $request->code,
                'sku' => $request->sku,
                'tax' => $request->tax,
                'slug' => $request->slug ?? Str::slug($request->title),
                'description' => $request->description,
                'seq' => $request->seq ?? 0,
            ]);

            foreach ($request->cat_id as $catId) {
                CatProMap::create([
                    'cat_id' => $catId,
                    'pro_id' => $product->id
                ]);
            }
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $key => $image) {
                    $uploadResult = UploadImage::upload(
                        $image,
                        'product_photos',
                        null,
                        2048
                    );


                    if ($uploadResult['res'] === 'success') {
                        ProductPhoto::create([
                            'pro_id' => $product->id,
                            'image' => $uploadResult['file_path'],
                            'thumbnail' => $uploadResult['file_path'],
                            'active' => true,
                            'seq' => $key + 1
                        ]);
                    }
                }
            }

            return response()->json([
                'message' => 'Product created successfully',
                'data' => $product
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong!',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function show($id)
    {
        $product = Product::with([
            'photos:id,pro_id,image,thumbnail,seq,active',
            'categories:id,title,parent_id',
            'categories.parent:id,title'
        ])->find($id);

        if (!$product) {
            return response()->json(['error' => 'Product not found.'], 404);
        }

        $product->photos->transform(function ($photo) {
            $photo->image = getImageUrl($photo->image);
            $photo->thumbnail = getImageUrl($photo->thumbnail);
            return $photo;
        });

        return response()->json($product, 200);
    }



    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['error' => 'Product not found.'], 404);
        }

        try {
            $request->validate([
                'title' => 'required|string',
                'cat_id' => 'required|array',
                'cat_id.*' => 'exists:categories,id',
                'code' => 'nullable|string|unique:products,code,' . $product->id,
                'sku' => 'nullable|string|unique:products,sku,' . $product->id,
                'tax' => 'nullable|numeric',
                'slug' => 'nullable|string|unique:products,slug,' . $product->id,
                'description' => 'nullable|string',
                'seq' => 'nullable|integer',
                'images' => 'nullable|array',
                'images.*' => 'file|mimes:jpeg,jpg,png,svg,webp,avif|max:2048',
            ]);

            $product->update([
                'title' => $request->title,
                'code' => $request->code,
                'sku' => $request->sku,
                'tax' => $request->tax,
                'slug' => $request->slug ?? Str::slug($request->title),
                'description' => $request->description,
                'seq' => $request->seq ?? 0,
            ]);

            CatProMap::where('pro_id', $product->id)->delete();
            foreach ($request->cat_id as $catId) {
                CatProMap::create([
                    'cat_id' => $catId,
                    'pro_id' => $product->id
                ]);
            }

            if ($request->hasFile('images')) {
                $oldPhotos = ProductPhoto::where('pro_id', $product->id)->get();
                foreach ($oldPhotos as $photo) {
                    UploadImage::deleteFile($photo->image);
                    $photo->delete();
                }

                foreach ($request->file('images') as $index => $imageFile) {
                    $tempRequest = new Request();
                    $tempRequest->files->set('image', $imageFile);

                    $upload = UploadImage::upload(
                        $tempRequest,
                        'product_photos',
                        null,
                        2048
                    );

                    if ($upload['res'] === 'success') {
                        ProductPhoto::create([
                            'pro_id' => $product->id,
                            'image' => $upload['file_path'],
                            'thumbnail' => $upload['file_path'],
                            'active' => true,
                            'seq' => $index + 1
                        ]);
                    }
                }
            }

            return response()->json([
                'message' => 'Product updated successfully.',
                'data' => $product->load('categories', 'photos')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Update failed.',
                'message' => $e->getMessage()
            ], 500);
        }
    }



    public function destroy(string $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['error' => 'Product not found.'], 404);
        }

        DB::beginTransaction();

        try {
            CatProMap::where('pro_id', $product->id)->delete();

            $photos = ProductPhoto::where('pro_id', $product->id)->get();
            foreach ($photos as $photo) {
                UploadImage::deleteFile($photo->image);
                if ($photo->thumbnail) {
                    UploadImage::deleteFile($photo->thumbnail);
                }
                $photo->delete();
            }
            $product->delete();

            DB::commit();

            return response()->json(['message' => 'Product and related data deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to delete product.',
                'message' => $e->getMessage()
            ], 500);
        }
    }


}
