<?php

/**
 * Regras de negócio para log de API
 */
class MdPesqApiLogRN extends InfraRN
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function inicializarObjInfraIBanco()
    {
        return BancoSEI::getInstance();
    }

    /**
     * Cadastra um novo log de API
     */
    public function cadastrar(MdPesqApiLogDTO $objMdPesqApiLogDTO)
    {
        try {
            $this->validarCadastro($objMdPesqApiLogDTO);
            
            $objMdPesqApiLogBD = new MdPesqApiLogBD($this->getObjInfraIBanco());
            $ret = $objMdPesqApiLogBD->cadastrar($objMdPesqApiLogDTO);
            
            return $ret;
            
        } catch (Exception $e) {
            throw new InfraException('Erro ao cadastrar log de API.', $e);
        }
    }

    /**
     * Lista logs de API
     */
    public function listar(MdPesqApiLogDTO $objMdPesqApiLogDTO)
    {
        try {
            $this->validarListar($objMdPesqApiLogDTO);
            
            $objMdPesqApiLogBD = new MdPesqApiLogBD($this->getObjInfraIBanco());
            $ret = $objMdPesqApiLogBD->listar($objMdPesqApiLogDTO);
            
            return $ret;
            
        } catch (Exception $e) {
            throw new InfraException('Erro ao listar logs de API.', $e);
        }
    }

    /**
     * Conta logs de API
     */
    public function contar(MdPesqApiLogDTO $objMdPesqApiLogDTO)
    {
        try {
            $this->validarContar($objMdPesqApiLogDTO);
            
            $objMdPesqApiLogBD = new MdPesqApiLogBD($this->getObjInfraIBanco());
            $ret = $objMdPesqApiLogBD->contar($objMdPesqApiLogDTO);
            
            return $ret;
            
        } catch (Exception $e) {
            throw new InfraException('Erro ao contar logs de API.', $e);
        }
    }

    /**
     * Exclui logs antigos (limpeza)
     */
    public function excluirAntigos($diasRetencao = 90)
    {
        try {
            $dataLimite = date('Y-m-d H:i:s', strtotime("-{$diasRetencao} days"));
            
            $objMdPesqApiLogDTO = new MdPesqApiLogDTO();
            $objMdPesqApiLogDTO->setDthAcesso($dataLimite, InfraDTO::$OPER_MENOR);
            $objMdPesqApiLogDTO->retNumIdLog();
            
            $objMdPesqApiLogBD = new MdPesqApiLogBD($this->getObjInfraIBanco());
            $arrLogs = $objMdPesqApiLogBD->listar($objMdPesqApiLogDTO);
            
            $totalExcluidos = 0;
            foreach ($arrLogs as $log) {
                $objMdPesqApiLogBD->excluir($log);
                $totalExcluidos++;
            }
            
            return $totalExcluidos;
            
        } catch (Exception $e) {
            throw new InfraException('Erro ao excluir logs antigos.', $e);
        }
    }

    /**
     * Gera relatório de uso da API
     */
    public function gerarRelatorioUso($dataInicio = null, $dataFim = null)
    {
        try {
            if (!$dataInicio) {
                $dataInicio = date('Y-m-d', strtotime('-30 days'));
            }
            
            if (!$dataFim) {
                $dataFim = date('Y-m-d');
            }
            
            $objMdPesqApiLogDTO = new MdPesqApiLogDTO();
            $objMdPesqApiLogDTO->setDthAcesso($dataInicio . ' 00:00:00', InfraDTO::$OPER_MAIOR_IGUAL);
            $objMdPesqApiLogDTO->setDthAcesso($dataFim . ' 23:59:59', InfraDTO::$OPER_MENOR_IGUAL);
            $objMdPesqApiLogDTO->retStrEndpoint();
            $objMdPesqApiLogDTO->retStrMetodo();
            $objMdPesqApiLogDTO->retDthAcesso();
            $objMdPesqApiLogDTO->retNumCodigoResposta();
            $objMdPesqApiLogDTO->retNumTempoExecucao();
            $objMdPesqApiLogDTO->retStrIpUsuario();
            
            $objMdPesqApiLogBD = new MdPesqApiLogBD($this->getObjInfraIBanco());
            $arrLogs = $objMdPesqApiLogBD->listar($objMdPesqApiLogDTO);
            
            return $this->processarRelatorioUso($arrLogs);
            
        } catch (Exception $e) {
            throw new InfraException('Erro ao gerar relatório de uso.', $e);
        }
    }

    /**
     * Processa dados para o relatório de uso
     */
    private function processarRelatorioUso($arrLogs)
    {
        $relatorio = array(
            'total_requisicoes' => count($arrLogs),
            'endpoints_mais_usados' => array(),
            'metodos_por_endpoint' => array(),
            'codigos_resposta' => array(),
            'tempo_medio_execucao' => 0,
            'ips_unicos' => array(),
            'requisicoes_por_dia' => array()
        );
        
        $endpointCount = array();
        $metodosCount = array();
        $codigosCount = array();
        $tempoTotal = 0;
        $tempoCount = 0;
        $ipsUnicos = array();
        $diasCount = array();
        
        foreach ($arrLogs as $log) {
            // Endpoints mais usados
            $endpoint = $log->getStrEndpoint();
            if (!isset($endpointCount[$endpoint])) {
                $endpointCount[$endpoint] = 0;
            }
            $endpointCount[$endpoint]++;
            
            // Métodos por endpoint
            $metodo = $log->getStrMetodo();
            if (!isset($metodosCount[$endpoint])) {
                $metodosCount[$endpoint] = array();
            }
            if (!isset($metodosCount[$endpoint][$metodo])) {
                $metodosCount[$endpoint][$metodo] = 0;
            }
            $metodosCount[$endpoint][$metodo]++;
            
            // Códigos de resposta
            $codigo = $log->getNumCodigoResposta();
            if ($codigo) {
                if (!isset($codigosCount[$codigo])) {
                    $codigosCount[$codigo] = 0;
                }
                $codigosCount[$codigo]++;
            }
            
            // Tempo médio de execução
            $tempo = $log->getNumTempoExecucao();
            if ($tempo && $tempo > 0) {
                $tempoTotal += $tempo;
                $tempoCount++;
            }
            
            // IPs únicos
            $ip = $log->getStrIpUsuario();
            if ($ip && !in_array($ip, $ipsUnicos)) {
                $ipsUnicos[] = $ip;
            }
            
            // Requisições por dia
            $data = date('Y-m-d', strtotime($log->getDthAcesso()));
            if (!isset($diasCount[$data])) {
                $diasCount[$data] = 0;
            }
            $diasCount[$data]++;
        }
        
        // Ordena endpoints por uso
        arsort($endpointCount);
        $relatorio['endpoints_mais_usados'] = array_slice($endpointCount, 0, 10, true);
        
        $relatorio['metodos_por_endpoint'] = $metodosCount;
        $relatorio['codigos_resposta'] = $codigosCount;
        
        if ($tempoCount > 0) {
            $relatorio['tempo_medio_execucao'] = round($tempoTotal / $tempoCount, 2);
        }
        
        $relatorio['ips_unicos'] = count($ipsUnicos);
        
        // Ordena requisições por dia
        ksort($diasCount);
        $relatorio['requisicoes_por_dia'] = $diasCount;
        
        return $relatorio;
    }

    /**
     * Validações para cadastro
     */
    private function validarCadastro(MdPesqApiLogDTO $objMdPesqApiLogDTO)
    {
        $this->validarStrEndpoint($objMdPesqApiLogDTO);
        $this->validarStrMetodo($objMdPesqApiLogDTO);
        $this->validarDthAcesso($objMdPesqApiLogDTO);
    }

    /**
     * Validações para listagem
     */
    private function validarListar(MdPesqApiLogDTO $objMdPesqApiLogDTO)
    {
        // Sem validações específicas para listagem
    }

    /**
     * Validações para contagem
     */
    private function validarContar(MdPesqApiLogDTO $objMdPesqApiLogDTO)
    {
        // Sem validações específicas para contagem
    }

    /**
     * Valida endpoint
     */
    private function validarStrEndpoint(MdPesqApiLogDTO $objMdPesqApiLogDTO)
    {
        if (!$objMdPesqApiLogDTO->isSetStrEndpoint()) {
            throw new InfraException('Endpoint não informado.');
        }
    }

    /**
     * Valida método HTTP
     */
    private function validarStrMetodo(MdPesqApiLogDTO $objMdPesqApiLogDTO)
    {
        if (!$objMdPesqApiLogDTO->isSetStrMetodo()) {
            throw new InfraException('Método HTTP não informado.');
        }

        $metodosValidos = array('GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'HEAD', 'PATCH');
        if (!in_array($objMdPesqApiLogDTO->getStrMetodo(), $metodosValidos)) {
            throw new InfraException('Método HTTP inválido.');
        }
    }

    /**
     * Valida data/hora de acesso
     */
    private function validarDthAcesso(MdPesqApiLogDTO $objMdPesqApiLogDTO)
    {
        if (!$objMdPesqApiLogDTO->isSetDthAcesso()) {
            throw new InfraException('Data/hora de acesso não informada.');
        }
    }
}