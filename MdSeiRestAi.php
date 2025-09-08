<?php
/**
 * Módulo de integração de IA para o SEI
 *
 */

require_once 'Pen.php'; // Carrega a classe base do REST
require_once 'MdSeiRestAiRN.php';
require_once 'rn/MdRestAiPesquisaRN.php';
require_once 'infra/MdPesqCriptografia.php';
require_once 'infra/MdPesqSolrUtilExterno.php';

class MdSeiRestAi extends Pen
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getNome()
    {
        return 'Módulo de IA para o SEI';
    }

    public function getVersao()
    {
        return '1.1.0'; // Versão incrementada para refletir a autonomia
    }

    public function getInstituicao()
    {
        return 'Jherberth';
    }

    /**
     * Rotas do módulo
     *
     * @return array
     */
    public function getRoutes()
    {
        $routes = array(
            'resumo' => array(
                'handler' => 'MdSeiRestAiRN::gerarResumo',
                'methods' => array('POST'),
                'parameters' => array(
                    'id_documento' => Pen::REQ
                )
            ),

            'pesquisa' => array(
                'handler' => 'MdRestAiPesquisaRN::pesquisarPublica',
                'methods' => array('GET', 'POST'),
                'parameters' => array(
                    'q' => Pen::OPT,
                    'descricao' => Pen::OPT,
                    'observacao' => Pen::OPT,
                    'inicio' => Pen::OPT,
                    'quantidade' => Pen::OPT,
                    'orgaos' => Pen::OPT,
                    'id_unidade' => Pen::OPT,
                    'id_tipo_procedimento' => Pen::OPT,
                    'id_tipo_documento' => Pen::OPT,
                    'id_participante' => Pen::OPT,
                    'data_inicio' => Pen::OPT,
                    'data_fim' => Pen::OPT,
                    'escopo' => Pen::OPT,
                    'id_orgao_acesso_externo' => Pen::OPT,
                )
            ),
        );
        return $routes;
    }
}