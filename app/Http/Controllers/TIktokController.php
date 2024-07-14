<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Telegram\Bot\Api;
use Telegram\Bot\Laravel\Facades\Telegram;

class TIktokController extends Controller
{
   public function index()
   {
	  
   }
   
   public function getProduct()
   {
	  return inertia('tiktok/show-product');
   }
   
   public function productScrapper(Request $request)
   {
	  $validator = Validator::make($request->all(), [
		 'url' => 'required|url'
	  ]);
	  
	  if ($validator->fails()) {
		 return redirect()->back()->withErrors($validator)->withInput();
	  }
	  
	  $url = $request->input('url');
	  $response = Http::post('http://localhost:3333/tikthop-scrapper', [
		 'url' => $url
	  ])->collect();
	  
	  $image_links = $response['data']['imgLinks'];
	  
	  $media = [];
	  foreach ($image_links as $image_link) {
		 $type = [
			'type' => 'photo',
			'media' => $image_link,
			'parse_mode' => 'HTML'
		 ];
		 array_push($media, $type);
	  }

	  $media[0]['caption'] = $response['data']['title'];
	  $response = Telegram::bot('mybot');
	  $response->sendMediaGroup([
		 'chat_id' => "-1002243059927",
		 'message_thread_id' => 3,
		 'media' => json_encode($media),
	  ]);
	  
	  return redirect()->back()->with('success', 'Product has been scrapped successfully');
	 
   }
   
   private function saveImageFromUrl($imageUrl)
   {
	  try {
		 $imageData = file_get_contents($imageUrl);
		 $filename = basename($imageUrl);
		 // Simpan gambar ke storage/app/public
		 Storage::disk('public')->put($filename, $imageData);
		 // Mengembalikan path gambar yang disimpan
		 return $filename;
	  } catch (Exception $exception) {
		 throw new RuntimeException($exception->getMessage());
	  }
   }
}
