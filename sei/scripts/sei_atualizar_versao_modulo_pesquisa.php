<?
require_once dirname(__FILE__) . '/../web/SEI.php';

class MdPesqAtualizadorSeiRN extends InfraRN
{

    private $numSeg = 0;
    private $versaoAtualDesteModulo = '4.4.0';
    private $nomeDesteModulo = 'M�DULO DE PESQUISA P�BLICA';
    private $nomeParametroModulo = 'VERSAO_MODULO_PESQUISA_PUBLICA';
    private $historicoVersoes = array('3.0.0', '4.0.0', '4.0.1', '4.1.0', '4.2.0','4.3.0','4.4.0');

    public function __construct()
    {
        parent::__construct();
    }

    protected function inicializarObjInfraIBanco()
    {
        return BancoSEI::getInstance();
    }

    protected function inicializar($strTitulo)
    {
        session_start();
        SessaoSEI::getInstance(false);
		
        ini_set('max_execution_time', '0');
        ini_set('memory_limit', '-1');
        @ini_set('implicit_flush', '1');
        ob_implicit_flush();

        InfraDebug::getInstance()->setBolLigado(true);
        InfraDebug::getInstance()->setBolDebugInfra(true);
        InfraDebug::getInstance()->setBolEcho(true);
        InfraDebug::getInstance()->limpar();

        $this->numSeg = InfraUtil::verificarTempoProcessamento();

        $this->logar($strTitulo);
    }

    protected function logar($strMsg)
    {
        InfraDebug::getInstance()->gravar($strMsg);
        flush();
    }

    protected function finalizar($strMsg = null, $bolErro = false)
    {
        if (!$bolErro) {
            $this->numSeg = InfraUtil::verificarTempoProcessamento($this->numSeg);
            $this->logar('TEMPO TOTAL DE EXECU��O: ' . $this->numSeg . ' s');
        } else {
            $strMsg = 'ERRO: ' . $strMsg;
        }

        if ($strMsg != null) {
            $this->logar($strMsg);
        }

        InfraDebug::getInstance()->setBolLigado(false);
        InfraDebug::getInstance()->setBolDebugInfra(false);
        InfraDebug::getInstance()->setBolEcho(false);
        $this->numSeg = 0;
        die;
    }

    protected function atualizarVersaoConectado()
    {

        try {
            $this->inicializar('INICIANDO A INSTALA��O/ATUALIZA��O DO ' . $this->nomeDesteModulo . ' NO SEI VERS�O ' . SEI_VERSAO);

            //checando BDs suportados
            if (!(BancoSEI::getInstance() instanceof InfraMySql) &&
                !(BancoSEI::getInstance() instanceof InfraSqlServer) &&
                !(BancoSEI::getInstance() instanceof InfraOracle) &&
                !(BancoSEI::getInstance() instanceof InfraPostgreSql)) {
                $this->finalizar('BANCO DE DADOS N�O SUPORTADO: ' . get_parent_class(BancoSEI::getInstance()), true);
            }

            //testando versao do framework
            $numVersaoInfraRequerida = '2.37.1';
            if (version_compare(VERSAO_INFRA, $numVersaoInfraRequerida) < 0) {
                $this->finalizar('VERS�O DO FRAMEWORK PHP INCOMPAT�VEL (VERS�O ATUAL ' . VERSAO_INFRA . ', SENDO REQUERIDA VERS�O IGUAL OU SUPERIOR A ' . $numVersaoInfraRequerida . ')', true);
            }

            //checando permissoes na base de dados
            $objInfraMetaBD = new InfraMetaBD(BancoSEI::getInstance());

            if (count($objInfraMetaBD->obterTabelas('sei_teste')) == 0) {
                BancoSEI::getInstance()->executarSql('CREATE TABLE sei_teste (id ' . $objInfraMetaBD->tipoNumero() . ' null)');
            }

            BancoSEI::getInstance()->executarSql('DROP TABLE sei_teste');

            $objInfraParametro = new InfraParametro(BancoSEI::getInstance());

            $strVersaoModuloPesquisa = $objInfraParametro->getValor($this->nomeParametroModulo, false);

            switch ($strVersaoModuloPesquisa) {
                case '':
                    $this->instalarv300();
                case '3.0.0':
                    $this->instalarv400();
                case '4.0.0':
                    $this->instalarv401();
                case '4.0.1':
                    $this->instalarv410();
	            case '4.1.0':
		            $this->instalarv420();
                case '4.2.0':
		            $this->instalarv430();
                case '4.3.0':
		            $this->instalarv440();
                    break;

                default:
                    $this->finalizar('A VERS�O MAIS ATUAL DO ' . $this->nomeDesteModulo . ' (v' . $this->versaoAtualDesteModulo . ') J� EST� INSTALADA.');
                    break;

            }

            $this->finalizar('FIM');
            InfraDebug::getInstance()->setBolDebugInfra(true);
        } catch (Exception $e) {
            InfraDebug::getInstance()->setBolLigado(true);
            InfraDebug::getInstance()->setBolDebugInfra(true);
            InfraDebug::getInstance()->setBolEcho(true);
            throw new InfraException('Erro instalando/atualizando vers�o.', $e);
        }
    }

    protected function instalarv300()
    {
        $nmVersao = '3.0.0';
		
        $objInfraMetaBD = new InfraMetaBD(BancoSEI::getInstance());

        $this->logar('EXECUTANDO A INSTALA��O/ATUALIZA��O DA VERS�O '. $nmVersao .' DO ' . $this->nomeDesteModulo . ' NA BASE DO SEI');

        $this->logar('CRIANDO A TABELA md_pesq_parametro');
        BancoSEI::getInstance()->executarSql('CREATE TABLE md_pesq_parametro (
					nome ' . $objInfraMetaBD->tipoTextoVariavel(100) . ' NOT NULL ,
					valor ' . $objInfraMetaBD->tipoTextoGrande() . ' NOT NULL
					)');

        $objInfraMetaBD->adicionarChavePrimaria('md_pesq_parametro', 'pk_md_pesq_parametro', array('nome'));

        $this->logar('TABELA md_pesq_parametro CRIADA COM SUCESSO');
        $this->logar('INSERINDO DADOS NA TABELA md_pesq_parametro');

        $arrParametroPesquisaDTO = array(
            array('Nome' => 'CAPTCHA', 'Valor' => 'S'),
            array('Nome' => 'CAPTCHA_PDF', 'Valor' => 'S'),
            array('Nome' => 'LISTA_ANDAMENTO_PROCESSO_PUBLICO', 'Valor' => 'S'),
            array('Nome' => 'PROCESSO_RESTRITO', 'Valor' => 'S'),
            array('Nome' => 'METADADOS_PROCESSO_RESTRITO', 'Valor' => 'S'),
            array('Nome' => 'LISTA_ANDAMENTO_PROCESSO_RESTRITO', 'Valor' => 'S'),
            array('Nome' => 'DESCRICAO_PROCEDIMENTO_ACESSO_RESTRITO', 'Valor' => 'Processo ou Documento de Acesso Restrito - Para condi��es de acesso verifique a <a style="font-size: 1em;" href="http://[orgao]/link_condicao_acesso" target="_blank">Condi��o de Acesso</a> ou entre em contato pelo e-mail: sei@orgao.gov.br'),
            array('Nome' => 'DOCUMENTO_PROCESSO_PUBLICO', 'Valor' => 'S'),
            array('Nome' => 'LISTA_DOCUMENTO_PROCESSO_PUBLICO', 'Valor' => 'S'),
            array('Nome' => 'LISTA_DOCUMENTO_PROCESSO_RESTRITO', 'Valor' => 'S'),
            array('Nome' => 'AUTO_COMPLETAR_INTERESSADO', 'Valor' => 'S'),
            array('Nome' => 'MENU_USUARIO_EXTERNO', 'Valor' => 'S'),
            array('Nome' => 'CHAVE_CRIPTOGRAFIA', 'Valor' => 'ch@c3_cr1pt0gr@f1a'),
        );

        $arrObjParametroPesquisaDTO = InfraArray::gerarArrInfraDTOMultiAtributos('MdPesqParametroPesquisaDTO', $arrParametroPesquisaDTO);

        $objParametroPesquisaRN = new MdPesqParametroPesquisaRN();

        foreach ($arrObjParametroPesquisaDTO as $objParametroPesquisaDTO) {

            $objParametroPesquisaRN->cadastrar($objParametroPesquisaDTO);
        }
		
		$this->logar('ADICIONANDO PAR�METRO ' . $this->nomeParametroModulo . ' NA TABELA infra_parametro PARA CONTROLAR A VERS�O DO M�DULO');
        BancoSEI::getInstance()->executarSql('INSERT INTO infra_parametro (valor, nome) VALUES( \'3.0.0\',  \'' . $this->nomeParametroModulo . '\' )');
        $this->logar('INSTALA��O/ATUALIZA��O DA VERS�O '. $nmVersao .' DO ' . $this->nomeDesteModulo . ' REALIZADA COM SUCESSO NA BASE DO SEI');
    }

    protected function instalarv400()
    {
        $nmVersao = '4.0.0';

        $objInfraMetaBD = new InfraMetaBD(BancoSEI::getInstance());
        $objInfraMetaBD->setBolValidarIdentificador(true);

        $this->logar('EXECUTANDO A INSTALA��O/ATUALIZA��O DA VERS�O '. $nmVersao .' DO ' . $this->nomeDesteModulo . ' NA BASE DO SEI');

        $arrTabelas = array('md_pesq_parametro');

        $this->fixIndices($objInfraMetaBD, $arrTabelas);

        $this->atualizarNumeroVersao($nmVersao);
    }

    protected function instalarv401()
    {
        $nmVersao = '4.0.1';

        $this->logar('EXECUTANDO A INSTALA��O/ATUALIZA��O DA VERS�O '. $nmVersao .' DO ' . $this->nomeDesteModulo . ' NA BASE DO SEI');

        $this->atualizarNumeroVersao($nmVersao);
    }

    protected function instalarv410()
    {
        $nmVersao = '4.1.0';
        
        $objInfraMetaBD = new InfraMetaBD(BancoSEI::getInstance());
        $objInfraMetaBD->setBolValidarIdentificador(true);

        $this->logar('EXECUTANDO A INSTALA��O/ATUALIZA��O DA VERS�O '. $nmVersao .' DO ' . $this->nomeDesteModulo . ' NA BASE DO SEI');

        $this->logar('ALTERANDO COLUNA valor NA TABELA md_pesq_parametro PARA ACEITAR VALOR NULO ANTES DE ADICIONAR O PARAMETRO DATA_CORTE');
        if (BancoSEI::getInstance() instanceof InfraOracle) {
        	
            BancoSEI::getInstance()->executarSql('alter table md_pesq_parametro rename column valor to valor_old');
            $objInfraMetaBD->adicionarColuna('md_pesq_parametro', 'valor', $objInfraMetaBD->tipoTextoGrande(), 'NULL');
            BancoSEI::getInstance()->executarSql('UPDATE md_pesq_parametro SET valor = valor_old');
            $objInfraMetaBD->excluirColuna('md_pesq_parametro','valor_old');
            
        } else if (BancoSEI::getInstance() instanceof InfraPostgreSql) {
	
	        BancoSEI::getInstance()->executarSql('ALTER TABLE md_pesq_parametro ALTER COLUMN valor DROP NOT NULL');
	
        }else {
        	
            $objInfraMetaBD->alterarColuna('md_pesq_parametro', 'valor', $objInfraMetaBD->tipoTextoGrande(), 'NULL');
            
        }
        
        $this->logar('INSERINDO NOVO PAR�METRO "DATA_CORTE" NA TABELA md_pesq_parametro');

        $MdPesqParametroPesquisaDTO = new MdPesqParametroPesquisaDTO();
        $MdPesqParametroPesquisaDTO->setStrNome('DATA_CORTE');
        $MdPesqParametroPesquisaDTO->setStrValor(null);
        $MdPesqParametroPesquisaDTO = (new MdPesqParametroPesquisaRN())->cadastrar($MdPesqParametroPesquisaDTO);

        $this->logar('REMOVENDO PARAMETRO "PROCESSO_RESTRITO" NA TABELA md_pesq_parametro');
        $mdPesqParametroPesquisaDTO = new MdPesqParametroPesquisaDTO();
        $mdPesqParametroPesquisaDTO->setStrNome('PROCESSO_RESTRITO');
        (new MdPesqParametroPesquisaBD(BancoSEI::getInstance()))->excluir($mdPesqParametroPesquisaDTO);

        $this->logar('ATUALIZANDO NOME DO PARAMETRO "DOCUMENTO_PROCESSO_PUBLICO" PARA "PESQUISA_DOCUMENTO_PROCESSO_RESTRITO"');
        $sqlTabela = 'UPDATE md_pesq_parametro SET nome=\'PESQUISA_DOCUMENTO_PROCESSO_RESTRITO\' WHERE nome =\'DOCUMENTO_PROCESSO_PUBLICO\'';
        BancoSEI::getInstance()->executarSql($sqlTabela);

        $this->atualizarNumeroVersao($nmVersao);
    }
    
    protected function instalarv420(){
	
	    $nmVersao = '4.2.0';
	
	    $this->logar('EXECUTANDO A INSTALA��O/ATUALIZA��O DA VERS�O '.$nmVersao.' DO ' . $this->nomeDesteModulo . ' NA BASE DO SEI');
	
	    $objInfraMetaBD = new InfraMetaBD(BancoSEI::getInstance());
	    $objInfraMetaBD->setBolValidarIdentificador(true);
	
	    $this->logar('>>>> MIGRANDO PAR�METROS DO UTILIDADES PARA O PESQUISA P�BLICA');
	
	    $arrStrModulos = [ 0 => 'UTILIDADES' , 1 => 'PESQUISA_PUBLICA'];
	
	    $strNomeAtual   = $arrStrModulos[0];
	    $strNomeQueSera = $arrStrModulos[1];
	
	    $arrParametros = array(
		    'MODULO_'.$strNomeAtual.'_BLOQUEAR_BLOQUEAR_PROCESSO_COM_DOCUMENTO_RESTRITO_USANDO_HIPOTESE_LEGAL',
		    'MODULO_'.$strNomeAtual.'_BLOQUEAR_CONCLUIR_PROCESSO_COM_DOCUMENTO_RESTRITO_USANDO_HIPOTESE_LEGAL',
	    );
	
	    $objInfraParametroRN = new InfraParametroRN();
	    $objInfraParametro   = new InfraParametro(BancoSEI::getInstance());
	
	    foreach ( $arrParametros as $str ) {
		
		    $arrNomeParam    = explode( '_' , $str );
		    $arrNomeParam[1] = $strNomeQueSera;
		    $strNovoParam    = implode( '_' , $arrNomeParam );
		
		    if ( $objInfraParametro->isSetValor( $str ) ){
			
			    $vlrParam = $objInfraParametro->getValor( $str );
			
			    // processo para cadastrar o parametro no modulo do peticionamento
			    $objInfraParametroDTO = new InfraParametroDTO();
			    $objInfraParametroDTO->setStrNome($strNovoParam);
			    $objInfraParametroDTO->setStrValor($vlrParam);
			    $objInfraParametroRN->cadastrar($objInfraParametroDTO);
			
			    $this->logar('------------------------------------------------------------------------');
			    $this->logar("Cadastrado o par�metro: $strNovoParam");
			    $this->logar('------------------------------------------------------------------------');
			
			    // processo para excluir o parametro usado como referencia do modulo utilidades
			    $objInfraParametroDTO = new InfraParametroDTO();
			    $objInfraParametroDTO->setStrNome($str);
			    $objInfraParametroDTO->retTodos();
			    $objInfraParametroDTO = $objInfraParametroRN->listar($objInfraParametroDTO);
			    $objInfraParametroRN->excluir($objInfraParametroDTO);
			    $this->logar('------------------------------------------------------------------------');
			    $this->logar("Exclu�do o par�metro: $str");
			    $this->logar('------------------------------------------------------------------------');
			
		    }else{
			
			    // processo para cadastrar o parametro no modulo do peticionamento
			    $objInfraParametroDTO = new InfraParametroDTO();
			    $objInfraParametroDTO->setStrNome($strNovoParam);
			    $objInfraParametroDTO->setStrValor(NULL);
			    $objInfraParametroRN->cadastrar($objInfraParametroDTO);
			
			    $this->logar('------------------------------------------------------------------------');
			    $this->logar("Cadastrado o par�metro: $strNovoParam");
			    $this->logar('------------------------------------------------------------------------');
		    	
		    }
		
	    }
	    
	    $excluirParametro = 'MODULO_UTILIDADES_BLOQUEAR_GERAR_PROCESSO_SEM_PELO_MENOS_UM_INTERESSADO';
	
	    // processo para excluir o parametro usado como referencia do modulo utilidades
	    $objInfraParametroDTO = new InfraParametroDTO();
	    $objInfraParametroDTO->setStrNome($excluirParametro);
	    $objInfraParametroDTO->retTodos();
	    $objInfraParametroDTO = $objInfraParametroRN->listar($objInfraParametroDTO);
	
	    $objInfraParametroRN->excluir($objInfraParametroDTO);
	    
	    $this->logar('------------------------------------------------------------------------');
	    $this->logar("Exclu�do o par�metro: $excluirParametro");
	    $this->logar('------------------------------------------------------------------------');
	    
	    $this->atualizarNumeroVersao($nmVersao);
    	
    }

    protected function instalarv430(){
        $nmVersao = '4.3.0';
        $this->logar('EXECUTANDO A INSTALA��O/ATUALIZA��O DA VERS�O '.$nmVersao.' DO ' . $this->nomeDesteModulo . ' NA BASE DO SEI');
        $this->atualizarNumeroVersao($nmVersao);
    }

    protected function instalarv440(){
        $nmVersao = '4.4.0';
        $this->logar('EXECUTANDO A INSTALA��O/ATUALIZA��O DA VERS�O '.$nmVersao.' DO ' . $this->nomeDesteModulo . ' NA BASE DO SEI');
        
        $objInfraMetaBD = new InfraMetaBD(BancoSEI::getInstance());
        
        // Executa o script de migração da API
        $this->logar('CRIANDO ESTRUTURAS PARA API REST v1...');
        
        // Cria tabela de chaves de API
        $this->logar('CRIANDO TABELA md_pesq_api_key...');
        BancoSEI::getInstance()->executarSql('CREATE TABLE md_pesq_api_key (' .
            'id_api_key ' . $objInfraMetaBD->tipoNumero() . ' NOT NULL, ' .
            'id_contato ' . $objInfraMetaBD->tipoNumero() . ' NOT NULL, ' .
            'api_key ' . $objInfraMetaBD->tipoTextoVariavel(255) . ' NOT NULL, ' .
            'sin_ativo ' . $objInfraMetaBD->tipoTextoFixo(1) . ' NOT NULL, ' .
            'dth_criacao ' . $objInfraMetaBD->tipoDataHora() . ' NOT NULL, ' .
            'dth_ultimo_acesso ' . $objInfraMetaBD->tipoDataHora() . ' NULL, ' .
            'dth_expiracao ' . $objInfraMetaBD->tipoDataHora() . ' NULL, ' .
            'descricao ' . $objInfraMetaBD->tipoTextoVariavel(500) . ' NULL, ' .
            'permissoes ' . $objInfraMetaBD->tipoTextoGrande() . ' NULL' .
        ')');
        
        // Adiciona chaves e constraints
        $objInfraMetaBD->adicionarChavePrimaria('md_pesq_api_key', 'pk_md_pesq_api_key', array('id_api_key'));
        $objInfraMetaBD->adicionarChaveEstrangeira('fk_md_pesq_api_key_contato', 'md_pesq_api_key', array('id_contato'), 'contato', array('id_contato'));
        
        // Cria tabela de logs de API
        $this->logar('CRIANDO TABELA md_pesq_api_log...');
        BancoSEI::getInstance()->executarSql('CREATE TABLE md_pesq_api_log (' .
            'id_log ' . $objInfraMetaBD->tipoNumero() . ' NOT NULL, ' .
            'id_api_key ' . $objInfraMetaBD->tipoNumero() . ' NULL, ' .
            'endpoint ' . $objInfraMetaBD->tipoTextoVariavel(500) . ' NOT NULL, ' .
            'metodo ' . $objInfraMetaBD->tipoTextoVariavel(10) . ' NOT NULL, ' .
            'parametros ' . $objInfraMetaBD->tipoTextoGrande() . ' NULL, ' .
            'dth_acesso ' . $objInfraMetaBD->tipoDataHora() . ' NOT NULL, ' .
            'ip_usuario ' . $objInfraMetaBD->tipoTextoVariavel(45) . ' NULL, ' .
            'user_agent ' . $objInfraMetaBD->tipoTextoGrande() . ' NULL, ' .
            'codigo_resposta ' . $objInfraMetaBD->tipoNumero() . ' NULL, ' .
            'tempo_execucao ' . $objInfraMetaBD->tipoNumero() . ' NULL' .
        ')');
        
        // Adiciona chaves e constraints
        $objInfraMetaBD->adicionarChavePrimaria('md_pesq_api_log', 'pk_md_pesq_api_log', array('id_log'));
        $objInfraMetaBD->adicionarChaveEstrangeira('fk_md_pesq_api_log_key', 'md_pesq_api_log', array('id_api_key'), 'md_pesq_api_key', array('id_api_key'));
        
        // Insere parâmetros de configuração da API se não existem
        $this->logar('INSERINDO PARÂMETROS DE CONFIGURAÇÃO DA API...');
        
        $arrParametrosApi = array(
            array('nome' => 'API_REST_HABILITADA', 'valor' => 'S'),
            array('nome' => 'API_REST_RATE_LIMIT', 'valor' => '1000'),
            array('nome' => 'API_REST_CORS_ORIGINS', 'valor' => '*'),
            array('nome' => 'API_REST_LOG_RETENCAO_DIAS', 'valor' => '90')
        );
        
        foreach ($arrParametrosApi as $parametro) {
            try {
                BancoSEI::getInstance()->executarSql("INSERT INTO md_pesq_parametro_pesquisa (id_parametro_pesquisa, nome, valor) VALUES " .
                    "(nextval('seq_md_pesq_parametro_pesquisa'), '" . $parametro['nome'] . "', '" . $parametro['valor'] . "')");
                $this->logar('Parâmetro ' . $parametro['nome'] . ' inserido com sucesso');
            } catch (Exception $e) {
                // Se o parâmetro já existe, apenas loga
                $this->logar('Parâmetro ' . $parametro['nome'] . ' já existe');
            }
        }
        
        $this->logar('ESTRUTURAS PARA API REST v1 CRIADAS COM SUCESSO');
        $this->atualizarNumeroVersao($nmVersao);
    }

	protected function fixIndices(InfraMetaBD $objInfraMetaBD, $arrTabelas)
    {
        InfraDebug::getInstance()->setBolDebugInfra(true);
        
        $this->logar('ATUALIZANDO INDICES...');
		
		$objInfraMetaBD->processarIndicesChavesEstrangeiras($arrTabelas);
		
		InfraDebug::getInstance()->setBolDebugInfra(false);
    }

	/**
	 * Atualiza o n�mero de vers�o do m�dulo na tabela de par�metro do sistema
	 *
	 * @param string $parStrNumeroVersao
	 * @return void
	 */
	private function atualizarNumeroVersao($parStrNumeroVersao)	{
		$this->logar('ATUALIZANDO PAR�METRO '. $this->nomeParametroModulo .' NA TABELA infra_parametro PARA CONTROLAR A VERS�O DO M�DULO');

		$objInfraParametroDTO = new InfraParametroDTO();
		$objInfraParametroDTO->setStrNome($this->nomeParametroModulo);
		$objInfraParametroDTO->retTodos();
		$objInfraParametroBD = new InfraParametroBD(BancoSEI::getInstance());
		$arrObjInfraParametroDTO = $objInfraParametroBD->listar($objInfraParametroDTO);

		foreach ($arrObjInfraParametroDTO as $objInfraParametroDTO) {
			$objInfraParametroDTO->setStrValor($parStrNumeroVersao);
			$objInfraParametroBD->alterar($objInfraParametroDTO);
		}
        
		$this->logar('INSTALA��O/ATUALIZA��O DA VERS�O '. $parStrNumeroVersao .' DO '. $this->nomeDesteModulo .' REALIZADA COM SUCESSO NA BASE DO SEI');
	}

}

try {

    SessaoSEI::getInstance(false);
    BancoSEI::getInstance()->setBolScript(true);

    $configuracaoSEI = new ConfiguracaoSEI();
    $arrConfig = $configuracaoSEI->getInstance()->getArrConfiguracoes();

    if (!isset($arrConfig['SEI']['Modulos'])) {
        throw new InfraException('PAR�METRO DE M�DULOS NO CONFIGURA��O DO SEI N�O DECLARADO');
    } else {
        $arrModulos = $arrConfig['SEI']['Modulos'];
        if (!key_exists('PesquisaIntegracao', $arrModulos)) {
            throw new InfraException('M�DULO PESQUISA P�BLICA N�O DECLARADO NA CONFIGURA��O DO SEI');
        }
    }

    if (!class_exists('PesquisaIntegracao')) {
        throw new InfraException('A CLASSE PRINCIPAL "PesquisaIntegracao" DO M�DULO N�O FOI ENCONTRADA');
    }

    InfraScriptVersao::solicitarAutenticacao(BancoSei::getInstance());
    $objVersaoSeiRN = new MdPesqAtualizadorSeiRN();
    $objVersaoSeiRN->atualizarVersao();
    exit;

} catch (Exception $e) {
    echo(InfraException::inspecionar($e));
    try {
        LogSEI::getInstance()->gravar(InfraException::inspecionar($e));
    } catch (Exception $e) {
    }
    exit(1);
}