<?php

/**
 * i-Educar - Sistema de gestão escolar
 *
 * Copyright (C) 2006  Prefeitura Municipal de Itajaí
 *                     <ctima@itajai.sc.gov.br>
 *
 * Este programa é software livre; você pode redistribuí-lo e/ou modificá-lo
 * sob os termos da Licença Pública Geral GNU conforme publicada pela Free
 * Software Foundation; tanto a versão 2 da Licença, como (a seu critério)
 * qualquer versão posterior.
 *
 * Este programa é distribuí­do na expectativa de que seja útil, porém, SEM
 * NENHUMA GARANTIA; nem mesmo a garantia implí­cita de COMERCIABILIDADE OU
 * ADEQUAÇÃO A UMA FINALIDADE ESPECÍFICA. Consulte a Licença Pública Geral
 * do GNU para mais detalhes.
 *
 * Você deve ter recebido uma cópia da Licença Pública Geral do GNU junto
 * com este programa; se não, escreva para a Free Software Foundation, Inc., no
 * endereço 59 Temple Street, Suite 330, Boston, MA 02111-1307 USA.
 *
 * @author    Lucas Schmoeller da Silva <lucas@portabilis.com.br>
 * @category  i-Educar
 * @license   @@license@@
 * @package   iEd_Pmieducar
 * @since     Arquivo disponível desde a versão 1.0.0
 * @version   $Id$
 */

require_once 'include/clsBase.inc.php';
require_once 'include/clsCadastro.inc.php';
require_once 'include/clsBanco.inc.php';
require_once 'include/pmieducar/geral.inc.php';

/**
 * clsIndexBase class.
 *
 * @author    Lucas Schmoeller da Silva <lucas@portabilis.com.br>
 * @category  i-Educar
 * @license   @@license@@
 * @package   iEd_Pmieducar
 * @since     Classe disponível desde a versão 1.0.0
 * @version   @@package_version@@
 */
class clsIndexBase extends clsBase
{
  function Formular()
  {
    $this->SetTitulo($this->_instituicao . ' i-Educar - Vagas por série');
    $this->processoAp = 21253;
    $this->addEstilo("localizacaoSistema");
  }
}

/**
 * indice class.
 *
 * @author    Lucas Schmoeller da Silva <lucas@portabilis.com.br>
 * @category  i-Educar
 * @license   @@license@@
 * @package   iEd_Pmieducar
 * @since     Classe disponível desde a versão 1.0.0
 * @version   @@package_version@@
 */
class indice extends clsCadastro
{
  var $pessoa_logada;

  var $cod_serie_vaga;
  var $ano;
  var $ref_cod_instituicao;
  var $ref_cod_escola;
  var $ref_cod_curso;
  var $ref_cod_serie;
  var $vagas;

  function Inicializar()
  {
    $retorno = 'Novo';
    @session_start();
    $this->pessoa_logada = $_SESSION['id_pessoa'];
    @session_write_close();

    $this->cod_serie_vaga = $_GET['cod_serie_vaga'];

    $obj_permissoes = new clsPermissoes();
    $obj_permissoes->permissao_cadastra(21253, $this->pessoa_logada, 7,
      'educar_serie_vaga_lst.php');

    if (is_numeric($this->cod_serie_vaga)) {
      $obj = new clsPmieducarSerieVaga($this->cod_serie_vaga);

      $registro  = $obj->detalhe();

      if ($registro) {
        // passa todos os valores obtidos no registro para atributos do objeto
        foreach ($registro as $campo => $val)   {
          $this->$campo = $val;
        }

        $obj_permissoes = new clsPermissoes();

        if ($obj_permissoes->permissao_excluir(21253, $this->pessoa_logada, 7)) {
          $this->fexcluir = TRUE;
        }

        $retorno = 'Editar';
      }
    }

    $this->url_cancelar = $retorno == 'Editar' ?
      sprintf('educar_serie_vaga_det.php?cod_serie_vaga=%d', $this->cod_serie_vaga) : 'educar_serie_vaga_lst.php';

    $this->nome_url_cancelar = 'Cancelar';

    $nomeMenu = $retorno == "Editar" ? $retorno : "Cadastrar";
        $localizacao = new LocalizacaoSistema();
        $localizacao->entradaCaminhos( array(
             $_SERVER['SERVER_NAME']."/intranet" => "In&iacute;cio",
             "educar_index.php"                  => "i-Educar - Escola",
             ""        => "{$nomeMenu} vagas por s&eacute;rie"
        ));
        $this->enviaLocalizacao($localizacao->montar());

    return $retorno;
  }

  function Gerar()
  {
    // primary keys
    $this->campoOculto('cod_serie_vaga', $this->cod_serie_vaga);

    $this->inputsHelper()->dynamic(array('ano', 'instituicao', 'escola', 'curso', 'serie'), array('disabled' => is_numeric($this->cod_serie_vaga)));

    $this->campoNumero('vagas', 'Vagas', $this->vagas, 3, 3, TRUE);
  }

  function Novo()
  {
    @session_start();
    $this->pessoa_logada = $_SESSION['id_pessoa'];
    @session_write_close();

    $obj_permissoes = new clsPermissoes();
    $obj_permissoes->permissao_cadastra(21253, $this->pessoa_logada, 7, 'educar_serie_vaga_lst.php');

    $sql = 'SELECT MAX(cod_serie_vaga) + 1 FROM pmieducar.serie_vaga';
    $db  = new clsBanco();
    $max_cod_serie = $db->CampoUnico($sql);
    $max_cod_serie = $max_cod_serie > 0 ? $max_cod_serie : 1;

    $obj = new clsPmieducarSerieVaga($max_cod_serie, $this->ano, $this->ref_cod_instituicao,
                                  $this->ref_cod_escola, $this->ref_cod_curso, $this->ref_cod_serie, $this->vagas);

    $lista = $obj->lista($this->ano, $this->ref_cod_escola, $this->ref_cod_curso, $this->ref_cod_serie);
    if(count($lista[0])){
      $this->mensagem = 'J&aacute; existe cadastro para est&aacute; s&eacute;rie/ano!<br />';
      return FALSE;
    }

    $cadastrou = $obj->cadastra();
    if ($cadastrou) {
      $this->mensagem .= 'Cadastro efetuado com sucesso.<br />';
      header('Location: educar_serie_vaga_lst.php');
      die();
    }

    $this->mensagem = 'Cadastro n&atilde;o realizado. Verifique se j&aacute; n&atilde;o existe cadastro para est&aacute; s&eacute;rie/ano!<br />';
    return FALSE;
  }

  function Editar()
  {
    @session_start();
    $this->pessoa_logada = $_SESSION['id_pessoa'];
    @session_write_close();

    $obj_permissoes = new clsPermissoes();
    $obj_permissoes->permissao_cadastra(21253, $this->pessoa_logada, 7, 'educar_serie_vaga_lst.php');

    $obj = new clsPmieducarSerieVaga($this->cod_serie_vaga);
    $obj->vagas = $this->vagas;

    $editou = $obj->edita();
    if ($editou) {
      $this->mensagem .= 'Edi&ccedil;&atilde;o efetuada com sucesso.<br />';
      header('Location: educar_serie_vaga_lst.php');
      die();
    }

    $this->mensagem = 'Edi&ccedil;&atilde;o não realizada.<br />';
    return FALSE;
  }

  function Excluir()
  {
    @session_start();
    $this->pessoa_logada = $_SESSION['id_pessoa'];
    @session_write_close();

    $obj_permissoes = new clsPermissoes();
    $obj_permissoes->permissao_excluir(21253, $this->pessoa_logada, 7, 'educar_serie_vaga_lst.php');

    $obj = new clsPmieducarSerieVaga($this->cod_serie_vaga);

    $excluiu = $obj->excluir();

    if ($excluiu) {
      $this->mensagem .= 'Exclus&atilde;o efetuada com sucesso.<br />';
      header('Location: educar_serie_vaga_lst.php');
      die();
    }

    $this->mensagem = 'Exclus&atilde;o não realizada.<br />';
    return FALSE;
  }
}

// Instancia objeto de página
$pagina = new clsIndexBase();

// Instancia objeto de conteúdo
$miolo = new indice();

// Atribui o conteúdo à  página
$pagina->addForm($miolo);

// Gera o código HTML
$pagina->MakeAll();