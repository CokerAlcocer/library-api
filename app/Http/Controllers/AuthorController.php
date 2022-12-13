<?php

namespace App\Http\Controllers;
use Exception;
use Illuminate\Http\Request;
use App\Models\Author;

class AuthorController extends Controller
{
    public function index()
    {
        $authors = Author::orderBy('first_surname')->with('books')->get();
        foreach($authors as $item){
            if($item->second_surname == NULL){
                unset($item->second_surname);
            }
            foreach($item->books as $b){
                unset($b->pivot, $b->category_id, $b->editorial_id);
            }
        }
        return $this->getResponse200($authors);
    }

    public function find($id)
    {
        $author = Author::find($id);
        if($author){
            if($author->second_surname == NULL){
                unset($author->second_surname);
            }
            foreach($author->books as $item){
                unset($item->pivot, $item->category_id, $item->editorial_id);
            }
            return $this->getResponse200($author);
        }else{
            return $this->getResponse404();
        }
    }

    public function store(Request $request)
    {
        try{
            $author = new Author();
            $author->name = $request->name;
            $author->first_surname = $request->first_surname;
            $author->second_surname = $request->second_surname;
            $author->save();

            return $this->getResponse201('author', 'created', $author);
        } catch(Exception $e){
            return $this->getResponse500([]);
        }
    }

    public function override(Request $request, $id){
        $author = Author::find($id);

        try {
            if ($author) {
                $author->name = $request->name;
                $author->first_surname = $request->first_surname;
                $author->second_surname = $request->second_surname;
                $author->update();

                return $this->getResponse201('author', 'updated', $author);
            } else {
                return $this->getResponse404();
            }
        } catch (Exception $e) {
            return $this->getResponse500([]);
        }
    }

    public function remove($id)
    {
        $author = Author::find($id);
        if ($author != null) {
            $author->books()->detach();
            $author->delete();
            return $this->getResponseDelete200('author');
        }else {
            return $this->getResponse404();
        }
    }
}
