<?php

namespace App\Http\Controllers;
use App\Models\Book;
use App\Models\BookDownload;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    public function index()
    {
        $books = Book::orderBy('title')->with('authors', 'editorial', 'category', 'bookDownload', 'reviews')->get();
        foreach($books as $item){
            unset($item->category_id, $item->editorial_id); // Quita los id`s de la editorial y categoria
            foreach($item->authors as $a){
                unset($a->pivot); // Quita los id`s de la tabla pivote
                if($a->second_surname == NULL){
                    unset($a->second_surname); // Quita el segundo apellido si es que este llega nulo
                }
                
            }
            foreach($item->reviews as $r){
                unset($r->book_id, $r->user_id); // Quita los id`s de la tabla pivote
            }
        }
        return $this->getResponse200($books);
    }

    public function find($id)
    {
        $book = Book::with('authors', 'editorial', 'category', 'reviews')->where('id', $id)->first();
        unset($book->category_id, $book->editorial_id); // Quita los id`s de la editorial y categoria
        foreach($book->authors as $item){
            unset($item->pivot); // Quita los id`s de la tabla pivote
            if($item->second_surname == NULL){
                unset($item->second_surname); // Quita el segundo apellido si es que este llega nulo
            }
            
        }
        foreach($book->reviews as $item){
            unset($item->book_id, $item->user_id); // Quita los id`s de la tabla pivote
        }

        if($book){
            return $this->getResponse200($book);
        }else{
            return $this->getResponse404();
        }
    }

    public function store(Request $request)
    {
        try {
            $isbn = preg_replace('/\s+/', ' ', $request->isbn); //Remove blank spaces from ISBN
            $existIsbn = Book::where("isbn", $isbn)->exists(); //Check if a registered book exists (duplicate ISBN)
            if (!$existIsbn) { //ISBN not registered
                $book = new Book();
                $book->isbn = $isbn;
                $book->title = $request->title;
                $book->description = $request->description;
                $book->published_date = date('y-m-d h:i:s'); //Temporarily assign the current date
                $book->category_id = $request->category["id"];
                $book->editorial_id = $request->editorial["id"];
                $book->save();

                $bookDownload = new BookDownload();
                $bookDownload->book_id = $book->id;
                $bookDownload->save();

                foreach ($request->authors as $item) { //Associate authors to book (N:M relationship)
                    $book->authors()->attach($item);
                }
                return $this->getResponse201('book', 'created', $book);
            } else {
                return $this->getResponse500(['The isbn field must be unique']);
            }
        } catch (Exception $e) {
            return $this->getResponse500([]);
        }
    }

    public function override(Request $request, $id){
        //Busca el libro por id
        $book = Book::find($id);
        try {
            if ($book) {
                $isbn = trim($request->isbn);
                $isbnBook = Book::where('isbn', $isbn)->first();
                if (!$isbnBook || $isbnBook->id == $book->id) {
                    $book->isbn = $isbn;
                    $book->title = $request->title;
                    $book->description = $request->description;
                    $book->published_date = date('y-m-d h:i:s');
                    $book->category_id = $request->category['id'];
                    $book->editorial_id = $request->editorial['id'];
                    $book->update();

                    // Elimina o desvincula todos los autores
                    foreach ($book->authors as $item) {
                        $book->authors()->detach($item);
                    }

                    // Y agrega los nuevos todos los autores
                    foreach ($request->authors as $item) {
                        $book->authors()->attach($item);
                    }
                    $book = Book::with('category', 'editorial', 'authors')->where('id', $id)->get();
                    return $this->getResponse201('book', 'updated', $book);
                } else {
                    return $this->getResponse400();
                }
            } else {
                return $this->getResponse404();
            }
        } catch (Exception $e) {
            return $this->getResponse500([]);
        }
    }

    public function remove($id){
        $book = Book::find($id);
        if ($book != null) {
            //Desvincula todos los autores de la tabla de intersección
            $bookDownload = BookDownload::where('book_id', $id)->first();
            $bookDownload->delete();

            $book->authors()->detach();
            $book->delete();
            return $this->getResponseDelete200('book');
        }else {
            return $this->getResponse404();
        }
    }
}
