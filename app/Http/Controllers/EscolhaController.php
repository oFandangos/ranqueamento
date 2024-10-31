<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\Declinio;
use App\Models\Ranqueamento;
use App\Models\Hab;
use App\Models\Escolha;
use App\Rules\EscolhaRule;
use App\Service\Utils;

class EscolhaController extends Controller
{

    public function form(){
        Gate::authorize('ciclo_basico');
        $ranqueamento = Ranqueamento::where('status',1)->first();
        $escolhas = Escolha::where('ranqueamento_id',$ranqueamento->id)
                            ->where('user_id', auth()->user()->id)
                            ->get();

        $habs = Hab::where('ranqueamento_id',$ranqueamento->id)
                    ->where(function ($query) {
                        $periodo = Utils::periodo();
                        $query->where('perhab', $periodo)
                              ->orWhere('permite_ambos_periodos', 1);
                    })->get();

        return view('escolhas.form',[
            'habs' => $habs,
            'escolhas' => $escolhas
        ]); 
    }

    public function store(Request $request){
        Gate::authorize('ciclo_basico');
        $ranqueamento = Ranqueamento::where('status',1)->first();

        $request->validate([
            'habs' => [ 'required', new EscolhaRule], 
        ]);

        foreach(array_filter($request->habs) as $prioridade=>$hab_id) {
            $escolha = Escolha::where('ranqueamento_id',$ranqueamento->id)
                                ->where('user_id', auth()->user()->id)
                                ->where('prioridade', $prioridade)
                                ->first();
            if(!$escolha) {
                $escolha = new Escolha;
                $escolha->ranqueamento_id = $ranqueamento->id;
                $escolha->user_id = auth()->user()->id;
                $escolha->prioridade = $prioridade;
            }
            $escolha->hab_id = $hab_id;
            $escolha->save();
        }
        return redirect("/");
    }

    public function declinar(Request $request){
        Gate::authorize('ciclo_basico');
        $ranqueamento = Ranqueamento::where('status',1)->first();

        if(!$ranqueamento) {
            $request->session()->flash('alert-danger','Não há ranqueamento ativo');
            return redirect("/");
        }
        
        if($request->declinar==1) {
            $declinio = new Declinio;
            $declinio->user_id = auth()->user()->id;
            $declinio->ranqueamento_id = $ranqueamento->id;
            $declinio->save();
        }

        if($request->declinar==0) {
            $declinio = Declinio::where('user_id', auth()->user()->id)
                                ->where('ranqueamento_id',$ranqueamento->id)->first();
            if($declinio) $declinio->delete();
        }

        return redirect("/");
    }
}
