<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
if (!defined('INDEX_CHECK')) die("INDEX_CHECK not defined.");

class deals extends Module{

    protected $dealsPerPage = 15;    
                
    /**
     * This function acts as the messenger between the CMS and this module.
     * 
     * @version    1.0
     * @since   0.8.0 
     */    
    function doAction( $action )
    {
        if( preg_match( '/view\/(.*?)($|\/)/i', $action, $did ) )
        {
            $action = 'view';
        }

        $this->objPage->addPagecrumb(array(
            array('url' => '/'.root().'modules/deals/', 'name' => 'Deals'),
        ));

        switch( strtolower( $action ) )
        {
            default:
            case 'view':
                $this->getDeal();
            break;

            case 'list':
                $this->listLatestDeals();
            break;


            case 'new':
                $this->getDealCreation();
            break;
        }
    }

    public function listLatestDeals()
    {
        $this->objSQL->getTable('
            SELECT *
                FROM $Pdeals
            LIMIT %d, %d',
            array( 0, $this->dealsPerPage )
        );
    }

    public function getDeal()
    {

    }

    public function getDealCreation()
    {

    }
    
}
?>