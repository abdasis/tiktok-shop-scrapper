<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
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
	  
	  try {
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
		 
		 //caption title dan link link untuk telegram
		 $caption = "<b>{$response['data']['title']}</b>\nLink Produk: <a href='{$response['data']['url']}'>{$response['data']['url']}</a>👈";
		 $media[0]['caption'] = $caption;
		 
		 $response = Telegram::bot('mybot');
		 $response->sendMediaGroup([
			'chat_id' => "-1002243059927",
			'message_thread_id' => 3,
			'media' => json_encode($media),
		 ]);
		 
		 return redirect()->back()->with('success', 'Product has been scrapped successfully');
	  } catch (Exception $exception) {
		 return redirect()->back()->with('error', $exception->getMessage());
	  }
	  
   }
   
   public function webhook()
   {
	  $response = Telegram::bot('mybot');
	  $response_collection = $response->getWebhookUpdate()->collect();
	  Log::info($response_collection);
	  $pesan = $response_collection->value('text');
	  $chat_id = $response_collection->value('chat')['id'];
	  $username = $response_collection->value('from')['username'];
	  //validasi pesan harus mengandung https://vt.tokopedia.com dan https://shop-id.tokopedia.com
	  
	  if ($response_collection->value('chat')['username'] != 'ttaffl' && $response_collection->value('message')['message_thread_id'] != 109) {
		 return false;
	  }
	  //validasi pesan harus mengandung https://vt.tokopedia.com dan https://shop-id.tokopedia.com
	  if (strpos($pesan, "https://vt.tokopedia.com") !== false && strpos($pesan, "https://shop-id.tokopedia.com") !== false) {
		 $response->sendMessage([
			'chat_id' => "-1002243059927",
			'message_thread_id' => 109,
			'text' => 'Maaf kak @'.$username.', link produk yang kamu kirimkan tidak sesuai dengan ketentuan. Silahkan kirimkan pesan yang sesuai. contoh https://vt.tokopedia.com/t/ZSYsLAQ6S/',
			'parse_mode' => 'HTML'
		 ]);
		 return false;
	  }
	  
	  $response->sendMessage([
		 'chat_id' => "-1002243059927",
		 'message_thread_id' => 109,
		 'text' => 'Halo kak @'.$username.', Produk yang kamu kirimkan sedang saya proses, silahkan sambil di cek di Produk Terlaris ya 🥰',
		 'parse_mode' => 'HTML'
	  ]);
	  
	  try {
		 $url = $pesan;
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
		 
		 //caption title dan link link untuk telegram
		 $caption = "<b>{$response['data']['title']}</b>\nLink Produk: <a href='{$response['data']['url']}'>{$response['data']['url']}</a>👈\nProduk Milik: Kak @{$username}";
		 $media[0]['caption'] = $caption;
		 
		 $response = Telegram::bot('mybot');
		 $response->sendMediaGroup([
			'chat_id' => "-1002243059927",
			'message_thread_id' => 3,
			'media' => json_encode($media),
		 ]);
		 
		 return redirect()->back()->with('success', 'Product has been scrapped successfully');
	  } catch (Exception $exception) {
		 return redirect()->back()->with('error', $exception->getMessage());
	  }
	  
   }
   
   private function validateMessage(string $message): bool
   {
	  return strpos($message, 'https://vt.tokopedia.com') !== false && strpos($message, 'https://shop-id.tokopedia.com') !== false;
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
