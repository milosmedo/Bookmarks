<?php

namespace App\Http\Controllers;

use App\Bookmark;
use App\Category;
use App\Comment;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use JWTAuth;

class BookmarkController extends Controller
{
    public function getBookmarks(Request $request){
        $user_id = JWTAuth::user()->id;
        $category_id = $request->category_id;
        if(empty($category_id)){
            $bookmarks = Bookmark::where("user_id", $user_id)->get();
            return $bookmarks;
        }
        else{
            $category = Category::find($category_id);
            if($category->user_id != $user_id){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pod touto kategóriou nemôžeme nájsť žiadne výsledky, pretože patrí inému používateľovi.'
                ],409);
            }
            else{
                $bookmarks = Bookmark::where([
                    ["category_id",'=',$category_id],
                    ["user_id","=",$user_id]
                ])->get();
                return $bookmarks;
            }
        }
    }

    public function getUserName($id){
        $user_id = Bookmark::find($id)->user_id;
        $user_name = User::find($user_id)->name;
        $user_surname = User::find($user_id)->surname;
        $fullname = $user_name." ".$user_surname;
        return $fullname;
    }

    public function showBookmark($id){
        $user = JWTAuth::user();
        $user_id = $user->id;
        $bookmark = Bookmark::find($id);
        if($bookmark->user_id != $user_id && $bookmark->isVisible == 0 && $user->isAdmin == 0){
            return response()->json([
                'status' => 'error',
                'message' => 'Na zobrazenie tejto záložky nemáte právo.'
            ],401);
        }
        else
        return $bookmark;
    }

    public function createBookmark(Request $request){
        $user_id = JWTAuth::user()->id;
        $url = $request->url;
        $name = $request->name;
        $description = $request->description;
        $category_id = $request->category_id;
        $isVisible = $request->isVisible;
        $category_name = null;
        $exist = Bookmark::where([
            ['user_id', '=', $user_id],
            ['name', '=', $name]
        ])->first();

        if(!empty($category_id)){
            $category = Category::find($category_id);

            $category_name = $category->name;
            if(empty($category) && !empty($category_id)){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Kategoria neexistuje.'
                ],409);
            }
            elseif($category->user_id != $user_id){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Na priradenie tejto kategorie nemate pravo.'
                ],401);
            }
        }
        if(!empty($exist)){
            return response()->json([
                'status' => 'error',
                'message' => 'Zalozka s tymto nazvom uz existuje.'
            ],409);
        }
        elseif(empty($url)){
            return response()->json([
                'status' => 'error',
                'message' => 'Zalozka musi mat uvedenu URL adresu.'
            ],409);
        }
        elseif(empty($name)){
            return response()->json([
                'status' => 'error',
                'message' => 'Zalozka musi mat uvedeny nazov.'
            ],409);
        }
        else{
            if($isVisible == null){
                $isVisible = true;
            }
            DB::table('bookmarks')->insert([
                'user_id' => $user_id,
                'category_id' => $category_id,
                'category_name' => $category_name,
                'name' => $name,
                'url' => $url,
                'description' => $description,
                'isRead' => false,
                'isVisible' => $isVisible,
                'created_at' => Carbon::now(),
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'Zalozka bola uspesne vytvorena.',
            ],200);
        }
    }

    public function editBookmark($id, Request $request){
        $user = JWTAuth::user();
        $user_id = $user->id;
        $name = $request->name;
        $url = $request->url;
        $description = $request->description;
        $category_id = $request->category_id;
        $isVisible = $request->isVisible;
        $category = Category::find($category_id);

        if(!empty($category))
        $category_name = $category->name;
        else
            $category_name = null;
        $bookmark = Bookmark::find($id);
        $bookmarkWithName = Bookmark::where([
            ['user_id', '=', $user_id],
            ['name', '=', $name]
        ])->first();

        if(empty($bookmark)){
            return response()->json([
                'status' => 'error',
                'message' => 'Zalozka neexistuje.',
            ],409);
        }
        else{
            if(($bookmark->user_id != $user_id) && ($user->isAdmin == 0)){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Na upravu tejto zalozky nemate opravnenie.',
                ],401);
            }
            if(!empty($category_id)){
                if(empty($category) && !empty($category_id)){
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Kategoria neexistuje.'
                    ],409);
                }
                elseif(($category->user_id != $user_id) && ($user->isAdmin == 0)){
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Na priradenie tejto kategorie nemate pravo.'
                    ],401);
                }
            }
            if(!empty($bookmarkWithName) && ($bookmark->name != $name)){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Zalozka s tymto nazvom uz existuje.',
                ],409);
            }
            elseif(empty($url)){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Zalozka musi mat uvedenu URL adresu.'
                ],409);
            }
            elseif(empty($name)){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Zalozka musi mat uvedeny nazov.'
                ],409);
            }
            else{
                if(empty($description)){
                    $description = null;
                }
                if(empty($category_id)){
                    $category_id = null;
                }
                if($isVisible == null){
                    $isVisible = $bookmark->isVisible;
                }

                $bookmark->url = $url;
                $bookmark->name = $name;
                $bookmark->description = $description;
                $bookmark->category_id = $category_id;
                $bookmark->category_name = $category_name;
                $bookmark->isVisible = $isVisible;
                $bookmark->updated_at = Carbon::now();
                $bookmark->save();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Zalozka bola uspesne zmenena.'
                ],200);
            }
//

        }
    }

    public function markReadFlag($id){
        $user = JWTAuth::user();
        $user_id = $user->id;
        $bookmark = Bookmark::find($id);

        if(empty($bookmark)){
            return response()->json([
                'status' => 'error',
                'message' => 'Dana zalozka neexistuje.',
            ],409);
        }
        else{
            $flag = $bookmark->isRead;
            if(($bookmark->user_id != $user_id) && ($user->isAdmin == 0)){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Na zmenu oznacenia tejto zalozky nemate opravnenie.',
                ],401);
            }
            elseif ($flag == false){
                $bookmark->isRead = true;
                $bookmark->save();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Zalozka bola uspesne oznacena za precitanu',
                ],200);
            }
            else{
                $bookmark->isRead = false;
                $bookmark->save();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Zalozka bola uspesne oznacena za neprecitanu',
                ],200);
            }
        }
    }

    public function deleteBookmark($id){
        $user = JWTAuth::user();
        $user_id = $user->id;
        $bookmark = Bookmark::find($id);

        if(empty($bookmark)){
            return response()->json([
                'status' => 'error',
                'message' => 'Dana zalozka neexistuje.',
            ],409);
        }
        elseif(($user_id != $bookmark->user_id) && ($user->isAdmin == 0)){
            return response()->json([
                'status' => 'error',
                'message' => 'Na zmazanie tejto zalozky nemate pravo.',
            ],401);
        }
        else{
//            Vyberiem vsetky riadky v spojovacej tabulke, kde sa nachadza tento bookmark, teda vsetky zoznamy
            $all_bookmarks = DB::table('bookmarklists_bookmarks')->where([
                ['bookmark_id', '=', $bookmark->id],
            ])->get();

            //            Vytvorim pole, do ktoreho vlozim ich idcka
                $array_lists_id = array();
                foreach ($all_bookmarks as $one_list){
                    array_push($array_lists_id,[$one_list->bookmarklist_id]);
                }
                if(empty($array_lists_id)){
                    $bookmark->forceDelete();
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Bookmark bol uspesne odstraneny.',
                    ],200);
                }
                else{
//            Opakujem tolkokrat, kolko zoznamov s danym bookmarkom existuje
                foreach($array_lists_id as $list_id){
//                Najdem poradie bookmarku v danom zozname
                    $order = DB::table("bookmarklists_bookmarks")->where([
                        ['bookmarklist_id', '=', $list_id],
                        ['bookmark_id', '=', $id],
                    ])->first()->order;

//                Mazem bookmark zo zoznamu
                    DB::table("bookmarklists_bookmarks")->where([
                        ['bookmarklist_id', '=', $list_id],
                        ['bookmark_id', '=', $id],
                    ])->delete();

//                Vyberiem vsetky zaznamy, ktore maju poradie vyssie ako zaznam s bookmarkom, ktory idem odstranit
                    $all_bookmarks = DB::table('bookmarklists_bookmarks')->where([
                        ['bookmarklist_id','=', $list_id],
                        ['order', '>', $order],
                    ])->get();
//                 Ak take zaznamu neexistuju, t.j. mazany bookmark je posledny v poradi, tak neriesime zoradovanie
                    if(empty($all_bookmarks)){
                        $bookmark->forceDelete();
                        continue;
                    }
                    else{
                        //                Znovu vytvaram pole, pre zoradenie zvysnych bookmarkov v zozname
                        $array = array();
                        foreach ($all_bookmarks as $item_id){
                            array_push($array,[$item_id->id]);
                        }
                        $i = 0;
                        foreach ($all_bookmarks as $item){

                            $new = $item->order - 1;
                            DB::table('bookmarklists_bookmarks')->where('id', $array[$i])->update(['order' => $new]);
                            $i++;
                        }
                    }
                }
//                Nakoniec samotny bookmark zmazem (zmazu sa aj vsetky komentare)
                    $bookmark->forceDelete();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Bookmark bol uspesne odstraneny.',
                ],200);
            }
        }
    }

    public function searchBookmarks(Request $request){
        $user_id = JWTAuth::user()->id;
        $text = $request->text;
        $global = $request->global;
        $read = $request->read;
        $category = $request->category;

        if($category != null){
            $category_id = Category::where([
                ['user_id','=',$user_id],
                ['name','=',$category],
            ])->first()->id;
//            Kategoria neexistuje
            if(empty($category_id)){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Lutujeme, dana kategoria neexistuje.',
                ],409);
            }
            else{
//                Najdenie vsetkych bookmarkov pod danou kategoriou
                $all_category = Bookmark::where([
                    ['user_id','=',$user_id],
                    ['category_id','=',$category_id]
                ])->get();

//                Ak sa pod kategoriou nenasla ziadna zalozka
                if(empty($all_category)){
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Lutujeme, pod danou kategoriou sa nenasli ziadne vysledky.',
                    ],409);
                }
                elseif($read == 1){
//                    Vsetky zalozky pod danou kategoriou, ktore su oznacene za precitane
                    $all_read = [];
                    foreach ($all_category as $item_category){
                        if($item_category->isRead == 1)
                        $all_read[]= $item_category;
                    }

//                    Do vysledku ulozim vsetky zalozky, ktore obsahuju dany retazec
                    $result = [];
                    foreach ($all_read as $item_read){
                        if(str_contains($item_read->name, $text) || str_contains($item_read->url, $text) || str_contains($item_read->description, $text)){
                            $result[] = $item_read;
                        }
                    }
                    if(empty($result)){
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Lutujeme, poziadavkam nevyhovuju ziadne vysledky.',
                        ],409);
                    }
                    else
                        return $result;
                }
                elseif($read == 2){
//                    Vsetky zalozky pod danou kategoriou, ktore su oznacene za neprecitane
                    $all_unread = [];
                    foreach ($all_category as $item_category){
                        if($item_category->isRead == 0)
                            $all_unread[]= $item_category;
                    }

//                    Do vysledku ulozim vsetky zalozky, ktore obsahuju dany retazec
                    $result = [];
                    foreach ($all_unread as $item_unread){
                        if(str_contains($item_unread->name, $text) || str_contains($item_unread->url, $text) || str_contains($item_unread->description, $text)){
                            $result[] = $item_unread;
                        }
                    }
                    if(empty($result)){
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Lutujeme, poziadavkam nevyhovuju ziadne vysledky.',
                        ],409);
                    }
                    else
                        return $result;
                }
                else{
                    foreach ($all_category as $item_category) {
                        if (str_contains($item_category->name, $text) || str_contains($item_category->url, $text) || str_contains($item_category->description, $text)) {
                            $result[] = $item_category;
                        }
                    }
                    if(empty($result)){
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Lutujeme, poziadavkam nevyhovuju ziadne vysledky.',
                        ],409);
                    }
                    else
                        return $result;
                }
            }
        }
        else{
//            Vybrat zo vsetkych bookmarkov
            if($global == true){
                $all_global = Bookmark::where('isVisible', '=', true)->orWhere('user_id','=',$user_id)->get();

                $result = [];
                foreach ($all_global as $item_global){
                    if(str_contains($item_global->name, $text) || str_contains($item_global->url, $text) || str_contains($item_global->description, $text))
                        $result[]=$item_global;
                }
                if(empty($result)){
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Lutujeme, poziadavkam nevyhovuju ziadne vysledky.',
                    ],409);
                }
                else
                    return $result;
            }
//            Vybrat z vlastnych
            else{
                $all_private = Bookmark::where('user_id', '=', $user_id)->get();

//                Vybratie z vlastnych, precitanych
                if($read == 1){
                    $all_read = [];
                    foreach ($all_private as $item_private){
                        if($item_private->isRead == 1)
                            $all_read[]=$item_private;
                    }

                    $result = [];
                    foreach ($all_read as $item_read){
                        if(str_contains($item_read->name, $text) || str_contains($item_read->url, $text) || str_contains($item_read->description, $text))
                            $result[]=$item_read;
                    }
                    if(empty($result)){
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Lutujeme, poziadavkam nevyhovuju ziadne vysledky.',
                        ],409);
                    }
                    else
                        return $result;
                }
                elseif ($read == 2){

                    $all_unread = [];
                    foreach ($all_private as $item_private){
                        if($item_private->isRead == 0)
                            $all_unread[]=$item_private;
                    }

                    $result = [];
                    foreach ($all_unread as $item_unread){
                        if(str_contains($item_unread->name, $text) || str_contains($item_unread->url, $text) || str_contains($item_unread->description, $text))
                            $result[]=$item_unread;
                    }
                    if(empty($result)){
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Lutujeme, poziadavkam nevyhovuju ziadne vysledky.',
                        ],409);
                    }
                    else
                        return $result;
                }
//                Nezalezi na precitanosti
                else{
                    $result = [];
                    foreach ($all_private as $item_private){
                        if(str_contains($item_private->name, $text) || str_contains($item_private->url, $text) || str_contains($item_private->description, $text))
                            $result[]=$item_private;
                    }
                    if(empty($result)){
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Lutujeme, poziadavkam nevyhovuju ziadne vysledky.',
                        ],409);
                    }
                    else
                        return $result;
                }
            }
        }
    }
}