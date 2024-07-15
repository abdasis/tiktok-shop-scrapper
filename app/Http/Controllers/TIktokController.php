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
	  try {
		 $response = Telegram::bot('mybot');
		 $response_collection = $response->getWebhookUpdate();
		 $chat_colleciton = collect($response_collection->getMessage())->sortByDesc('date');
		 Log::info($chat_colleciton);
		 $pesan = $chat_colleciton['text'];
		 $chat_id = $chat_colleciton['chat']['id'];
		 $username = $chat_colleciton['reply_to_message']['from']['username'];
		 $first_name = $chat_colleciton['from']['first_name'];
		 $last_name = $chat_colleciton['from']['last_name'];
		 $full_name = $first_name . ' ' . $last_name;
		 if (isset($chat_colleciton['message_thread_id'])) {
			$tread_id = $chat_colleciton['message_thread_id'];
			if (strpos($pesan, "https://vt.tokopedia.com") === false && strpos($pesan, "https://shop-id.tokopedia.com") === false) {
			   $response->sendMessage([
				  'chat_id' => $chat_id,
				  'message_thread_id' => $tread_id,
				  'text' => 'Maaf kak '.$first_name.', link produk yang kamu kirimkan tidak sesuai dengan ketentuan. Silahkan kirimkan link product yang sesuai. contoh https://vt.tokopedia.com/t/ZSYsLAQ6S/',
				  'parse_mode' => 'HTML'
			   ]);
			   return false;
			} else {
			   if ($tread_id !== 109) {
				  $response->sendMessage([
					 'chat_id' => $chat_id,
					 'message_thread_id' => $tread_id,
					 'text' => 'Maaf kak '.$first_name.', kamu mengirim pesan product di topik yang salah, silahkan kirim ulang di topik <b>Request Product </b>',
				  ]);
			   }
			   $response->sendMessage([
				  'chat_id' => $chat_id,
				  'message_thread_id' => 109,
				  'text' => "Halo kak $first_name product yang kamu kirimkan sedang saya proses, silahkan tunggu dan sambil pantau topik <b>Produk Terlaris</b> ya 🥰",
				  'parse_mode' => 'HTML'
			   ]);
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
			   $caption = "<b>{$response['data']['title']}</b>\n\nLink Produk: <a href='{$response['data']['url']}'>{$response['data']['url']}</a>👈\n\nDibuat oleh: Kak $full_name 🥰";
			   $media[0]['caption'] = $caption;
			   
			   $response = Telegram::bot('mybot');
			   $response->sendMediaGroup([
				  'chat_id' => "-1002243059927",
				  'message_thread_id' => 3,
				  'media' => json_encode($media),
			   ]);
			   return true;
			}
		 }
		 else{
			Log::info('Diluar forums');
			return true;
		 }
		 return true;
		 
	  } catch (Exception $exception) {
		 report($exception->getMessage());
		 return true;
	  } finally {
		 return true;
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
