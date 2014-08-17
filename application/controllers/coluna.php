<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Coluna extends CI_Controller {
	
	/*
	 * Atributos opcionais para as views
	* public $layout;  define o layout default
	* public $title; define o titulo default da view
	* public $css = array('css1','css2'); define os arquivos css default
	* public $js = array('js1','js2'); define os arquivos javascript default
	* public $images = 'dir_images'; define a diretório default das imagens
	*
	*/
	
	public $layout = 'default';
	public $css = array('style','demo_page','demo_table_jui','jquery-ui-1.8.11.custom');
	public $js = array('jquery-1.7.1.min','jquery.dataTables.min','jquery.blockUI','about');
	public $js_custom;
	
    private $area = "coluna";
        
	public function __construct (){
		parent::__construct();	
		$this->load->library(array('restrict_page','table','form_validation','session'));
		$this->load->helper('url');
		$this->load->model('Coluna_model','',TRUE);
        $this->load->model('Grid_model','',TRUE);
        $this->modal = $this->load->view('about_modal', '', TRUE);
        session_start();
	}

	public function index($offset = 0){
		
		$this->js[] = 'coluna';
		
		$data['titulo']     = 'Campos';
		$data['link_add']   = anchor($this->area.'/add/','Adicionar',array('class'=>'add'));
		$data['link_back']  = anchor('documento/index/','Lista de Documentos',array('class'=>'back'));
		$data['form_action'] = site_url($this->area.'/search');
		
		// BUSCA
		$data['keyword_'.$this->area] = '';
		if(isset($_SESSION['keyword_'.$this->area]) == true and $_SESSION['keyword_'.$this->area] != null){
			$data['keyword_'.$this->area] = $_SESSION['keyword_'.$this->area];
			redirect($this->area.'/search/');
		}else{
			$data['keyword_'.$this->area] = 'pesquisa textual';
			$data['link_search_cancel'] = '';
		}
		// FIM DA BUSCA
		
		
		//Inicio da Paginacao
        $this->load->library('pagination');
        $maximo = 10;
        $uri_segment = 3;
        $inicio = (!$this->uri->segment($uri_segment, 0)) ? 0 : ($this->uri->segment($uri_segment, 0) - 1) * $maximo;
        $_SESSION['novoinicio'] = $this->uri->segment($uri_segment - 1, 'index').'/'.$this->uri->segment($uri_segment, 0);  //cria uma variavel de sessao para retornar a pagina correta apos visualizacao, delecao ou alteracao
        $config['base_url'] = site_url($this->area.'/index/');
        $config['total_rows'] = $this->Coluna_model->count_all();
        
        $config['per_page'] = $maximo;

        $this->pagination->initialize($config);

        // load datas
        $objetos = $this->Coluna_model->list_all();
        
       // echo"<pre>";
       // print_r($objetos);
       // echo"</pre>";
       
      
        // carregando os dados na tabela
        $this->load->library('table');
        $this->table->set_empty("&nbsp;");
        $this->table->set_heading('Item', 'Nome', 'Ações');
        
  
	 	foreach ($objetos as $key => $value){
            $this->table->add_row($key, $value,
                anchor($this->area.'/view/'.$value,'visualizar',array('class'=>'view')).' '.
                anchor($this->area.'/update/'.$value,'alterar',array('class'=>'update')).' '.
               anchor($this->area.'/delete/'.$value,'deletar',array('class'=>'delete','onclick'=>"return confirm('Deseja REALMENTE deletar esse campo?')"))
            );
        }

        //Monta a DataTable
        $tmpl = $this->Grid_model->monta_tabela_list();
        $this->table->set_template($tmpl);
        // Fim da DataTable

        $data['table'] = $this->table->generate();
        $data["total_rows"] = $config['total_rows'];
        $data['pagination'] = $this->pagination->create_links();

        $this->load->view($this->area.'/'.$this->area.'_list', $data);
      
       
      

	}
	
	public function add() {
	
		$this->load->library(array('form_validation'));
		$this->form_validation->set_error_delimiters('<div class="error_field"> <img class="img_align" src="{TPL_images}/error.png" alt="! " /> ', '</div>');
	
		$data['titulo'] = 'Novo Campo';
		$data['link_back']  = anchor($this->area.'/index/','Voltar',array('class'=>'back'));
		$data['form_action'] = site_url($this->area.'/add/');
		$data['mensagem'] = '';
	
		//constroe os campos que serao mostrados no formulario
		$this->load->model('Campo_model','',TRUE);
		$data['campoNome'] = $this->Campo_model->coluna('campoNome');
		$data['campoTamanho'] = $this->Campo_model->coluna('campoTamanho');
	
		if ($this->form_validation->run($this->area."/add") == FALSE) {
			$this->load->view($this->area . "/" . $this->area.'_edit', $data);
		} else {
			//cria o objeto com os dados passados via post
			$objeto_do_form = array(
					'nome' => mb_convert_case($this->input->post('campoNome'), MB_CASE_LOWER, "UTF-8"),
					'tamanho' => mb_convert_case($this->input->post('campoTamanho'), MB_CASE_LOWER, "UTF-8"),
			);
	
			//checa a existencia de registro com o mesmo nome para evitar duplicatas
	
			if ($this->db->field_exists($objeto_do_form['nome'], 'documento')){
	
				$data['mensagem'] = '<div class="error_field"> <img class="img_align" src="{TPL_images}/error.png" alt="! " /> O registro já existe </div>';
	
				$this->load->view($this->area . "/" . $this->area.'_edit', $data);
	
			}else{
	
				// Salva o registro
		
				$this->Coluna_model->save($objeto_do_form);
	
				$this->js_custom = 'var sSecs = 4;
                                function getSecs(){
                                    sSecs--;
                                    if(sSecs<0){ sSecs=59; sMins--; }
                                    $("#clock1").html(sSecs+" segundos...");
                                    setTimeout("getSecs()",1000);
                                    var s =  $("#clock1").html();
                                    if (s == "1 segundos..."){
                                        window.location.href = "' . site_url('/'.$this->area) . '";
                                    }
                                }
                                ';
	
				$data['mensagem'] = "<br /> Redirecionando em ";
				$data['mensagem'] .= '<span id="clock1"> ' . "<script>setTimeout('getSecs()',1000);</script> </span>";
				$data['link1'] = '';
				$data['link2'] = '';
	
				$this->load->view('success', $data);
	
			}
	
		}
	
	}
	
	function view($value){

		$data['titulo'] = 'Detalhes do campo';
		
        $data['message'] = '';
        
		$data['link_back'] = anchor($this->area.'/'.$_SESSION['novoinicio'],'Voltar',array('class'=>'back'));
		
		$data['objeto'] = $this->Coluna_model->get_by_nome($value);
		$data['tamanho_atual'] = $this->Coluna_model->tamanho_maximo($value);
		
		$this->load->view($this->area.'/'.$this->area.'_view', $data);

	}
	
public function update($nome) {

		$this->load->library('form_validation');
		$this->form_validation->set_error_delimiters('<div class="error_field"> <img class="img_align" src="{TPL_images}/error.png" alt="! " /> ', '</div>');
			
		// define as variaveis comuns
		$data['titulo'] = "Alteração de  órgão";
		$data['mensagem'] = '';
		$data['link_back'] = anchor($this->area.'/'.$_SESSION['novoinicio'],'Voltar',array('class'=>'back'));
		$data['form_action'] = site_url($this->area.'/update/'.$nome);

		//Constroe os campos do formulario
		$this->load->model('Campo_model','',TRUE);
		$data['campoNome'] = $this->Campo_model->coluna('campoNome');
		$data['campoTamanho'] = $this->Campo_model->coluna('campoTamanho');
			
		// Instancia um objeto com o resultado da consulta
		$obj = $this->Coluna_model->get_by_nome($nome);

		//Popula os campos com os dados do objeto
		$data['campoNome']['value'] = $obj['nome'];
		
		$data['tamanho_atual'] = $this->Coluna_model->tamanho_maximo($obj['nome']);
		
		if ($this->form_validation->run($this->area."/add") == FALSE) {

			$this->load->view($this->area.'/'.$this->area.'_edit', $data);
				
		} else {

			//cria um objeto com os dados passados via post
			$objeto_do_form = array(
               		'nome' => mb_convert_case($this->input->post('campoNome'), MB_CASE_LOWER, "UTF-8"),
					'tamanho' => mb_convert_case($this->input->post('campoTamanho'), MB_CASE_LOWER, "UTF-8"),
			);
			
			if($objeto_do_form['tamanho'] < $data['tamanho_atual']){
				
				$data['mensagem'] = '<div class="error_field"> <img class="img_align" src="{TPL_images}/error.png" alt="! " /> O tamanho deve ser maior do que o já utilizado </div>';
				
				$this->load->view($this->area . "/" . $this->area.'_edit', $data);
				
			}else{
			
				$this->Coluna_model->update($objeto_do_form);

				$this->js_custom = 'var sSecs = 4;
                                function getSecs(){
                                    sSecs--;
                                    if(sSecs<0){ sSecs=59; sMins--; }				
                                    $("#clock1").html(sSecs+" segundos.");		
                                    setTimeout("getSecs()",1000);		
                                    var s =  $("#clock1").html();
                                    if (s == "1 segundos."){			
                                        window.location.href = "' . site_url($this->area.'/'.$_SESSION['novoinicio']) . '";
                                    }
                                }     		
                                ';

				$data['mensagem'] = "<br /><br />Redirecionando em... ";
				$data['mensagem'] .= '<span id="clock1"> ' . "<script>setTimeout('getSecs()',1000);</script> </span>";
				$data['link1'] = '';
				$data['link2'] = '';

				$this->load->view('success', $data);
				
			}
					

		}
	}
	
	function delete($campo){
		
		$obj = $this->Coluna_model->get_by_nome($campo);
		
		//checa se o campo existe e se esta vazio
		if ($this->db->field_exists($campo, 'documento') and $obj['max_length'] == 0)
		{
			$this->Coluna_model->delete($campo);
		}
	
		redirect($this->area.'/index/'.$_SESSION['novoinicio']);
	}


    public function search($page = 1) { 
    	$this->js[] = 'coluna';
        $data['titulo'] = "Busca por colunas";
        $data['link_add']   = anchor($this->area.'/add/','Adicionar',array('class'=>'add'));
        $data['link_search_cancel'] = anchor($this->area.'/search_cancel/','CANCELAR PESQUISA',array('class'=>'button_cancel'));
        $data['form_action'] = site_url($this->area.'/search');

        $this->load->library(array('pagination', 'table'));
        
        if(isset($_SESSION['keyword_'.$this->area]) == true and $_SESSION['keyword_'.$this->area] != null and $this->input->post('search') == null){
        	$keyword = $_SESSION['keyword_'.$this->area];
        }else{
        	
        	$keyword = ($this->input->post('search') == null or $this->input->post('search') == "pesquisa textual") ? redirect($this->area.'/index/') : $this->input->post('search');
        	$_SESSION['keyword_'.$this->area] = $keyword;
        	
        }
        
        $maximo = 10;  
        $uri_segment = 3;  
        $_SESSION['novoinicio'] = $this->uri->segment($uri_segment - 1, 0).'/'.$this->uri->segment($uri_segment, 0); 
        $config['per_page'] = $maximo;    
        $config['base_url'] = site_url($this->area.'/search');
       // $config['total_rows'] = $this->Coluna_model->count_all_search($keyword);           
        
        $this->pagination->initialize($config);     
        $data['pagination'] = $this->pagination->create_links();
        $this->table->set_empty("&nbsp;");
        $this->table->set_heading('Item', 'Nome', 'Ações');
        
        $inicio = (!$this->uri->segment($uri_segment, 0)) ? 0 : ($this->uri->segment($uri_segment, 0) - 1) * $maximo;

        $rows = $this->Coluna_model->listAllSearchPag($keyword, $maximo, $inicio);   
            
        foreach ($rows as $o){

            $this->table->add_row($o->id, $o->nome,
                anchor($this->area.'/view/'.$o->id,'visualizar',array('class'=>'view')).' '.
                anchor($this->area.'/update/'.$o->id,'alterar',array('class'=>'update'))
              //  anchor($this->area.'/delete/'.$objeto->id,'deletar',array('class'=>'delete','onclick'=>"return confirm('Deseja REALMENTE deletar esse orgao?')"))
            );

        }
        
        //Monta a DataTable
        $tmpl = $this->Grid_model->monta_tabela_list();
        $this->table->set_template($tmpl);
        // Fim da DataTable

        $data['table'] = $this->table->generate();
        $data['total_rows'] = $config['total_rows'];
        $data['keyword_'.$this->area] = $keyword;    
                
        $this->load->view($this->area.'/'.$this->area.'_list', $data); 

    }
    
    public function search_cancel() {
    
    	$_SESSION['keyword_'.$this->area] = null;
    
    	redirect($this->area.'/index/');
    
    }
	
	// date_validation callback
	function valid_date($str){
		if(!preg_match('^(0[1-9]|1[0-9]|2[0-9]|3[01])-(0[1-9]|1[012])-([0-9]{4})$^', $str))
		{
			$this->validation->set_message('valid_date', 'date format is not valid. dd-mm-yyyy');
			return false;
		}
		else
		{
			return true;
		}
	}
}
?>