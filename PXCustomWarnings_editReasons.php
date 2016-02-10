<?php

class PXCustomWarnings_editReasons extends admin_members_warnings_reasons {
	public $html;
	
	public $form_code    = '';
	
	public $form_code_js = '';
	
	public function doExecute( ipsRegistry $registry ) 
	{
		
		$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'reasons_view' );
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_member' ), 'members' );
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_warnings_reasons' );
		
		$this->form_code	= $this->html->form_code	= 'module=warnings&amp;section=reasons&amp;';
		$this->form_code_js	= $this->html->form_code_js	= 'module=warnings&section=reasons&';
		
		
		switch ( $this->request['do'] )
		{
			case 'add':
				$this->form( 'add' );
				break;
				
			case 'edit':
				$this->form( 'edit' );
				break;
				
			case 'save':
				$this->save();
				break;
				
			case 'delete':
				$this->delete();
				break;
				
			case 'do_delete':
				$this->do_delete();
				break;
		
			case 'reorder':
				$this->reorder();
				break;
		
			default:
				$this->manage();
				break;
		}	
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
		return;
	}
	
	private function manage()
	{
		$reasons = array();
		$this->DB->build( array( 'select' => '*', 'from' => 'members_warn_reasons', 'order' => 'wr_order' ) );
		$this->DB->execute();
		while( $row = $this->DB->fetch() )
		{
			$reasons[ $row['wr_id'] ] = $row;
		}
		
		$this->registry->output->html .= $this->html->manage( $reasons );
		return;
	}
	
	private function form( $type )
	{
		$current = array();
		if ( $type == 'edit' )
		{
			$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'reasons_edit' );
			
			$id = intval( $this->request['id'] );
			$current = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'members_warn_reasons', 'where' => "wr_id={$id}" ) );
			if ( !$current['wr_id'] )
			{
				ipsRegistry::getClass('output')->showError( 'err_no_warn_reason', '112150', FALSE, '', 404 );
			}			
		}
		else
		{
			$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'reasons_add' );
		}
					
		$this->registry->output->html .= $this->html->form( $current );
		return;
	}
	
	private function save()
	{
		
		if ( !$this->request['name'] )
		{
			ipsRegistry::getClass('output')->showError( 'err_warning_reason_name', '112151', FALSE, '', 500 );
		}
				
		$save = array( 
			'wr_name'				=> $this->request['name'],
			'wr_points'				=> $this->request['points'],
			'wr_points_override'	=> intval( $this->request['points_override'] ),
			'wr_remove'				=> intval( $this->request['remove'] ),
			'wr_remove_unit'		=> ( $this->request['remove_unit'] == 'd' ) ? 'd' : 'h',
			'wr_remove_override'	=> intval( $this->request['remove_override'] )
			);
			
		if ( $this->request['id'] )
		{
			$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'reasons_edit' );
			
			$id = intval( $this->request['id'] );
			$current = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'members_warn_reasons', 'where' => "wr_id={$id}" ) );
			if ( !$current['wr_id'] )
			{
				ipsRegistry::getClass('output')->showError( 'err_no_warn_reason', '112152', FALSE, '', 500 );
			}
			
			$this->DB->update( 'members_warn_reasons', $save, "wr_id={$id}" );
			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['warn_reasons_edited'], $save['wr_name'] ) );
		}
		else
		{
			$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'reasons_add' );
			
			$top = $this->DB->buildAndFetch( array( 'select' => 'MAX(wr_id) as _top', 'from' => 'members_warn_reasons' ) );
			$save['wr_order'] = ++$top['_top'];
			$this->DB->insert( 'members_warn_reasons', $save );
			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['warn_reasons_created'], $save['wr_name'] ) );
		}
		
		$this->registry->output->redirect( "{$this->settings['base_url']}app=members&amp;module=warnings&amp;section=reasons", $this->lang->words['warn_reasons_saved'] );
		return;
	}
	
	private function delete()
	{	
		$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'reasons_delete' );
		
		$id = intval( $this->request['id'] );
		$current = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'members_warn_reasons', 'where' => "wr_id={$id}" ) );
		if ( !$current['wr_id'] )
		{
			ipsRegistry::getClass('output')->showError( 'err_no_warn_reason', '112153', FALSE, '', 404 );
		}
		
		$new = intval( $this->request['new'] );
		$new = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'members_warn_reasons', 'where' => "wr_id={$new}" ) );
		if ( !$current['wr_id'] )
		{
			ipsRegistry::getClass('output')->showError( 'err_no_warn_reason', '112154', FALSE, '', 500 );
		}
		
		$this->DB->delete( 'members_warn_reasons', "wr_id={$id}" );
		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['warn_reasons_deleted'], $current['wl_name'] ) );
		
		$this->registry->output->redirect( "{$this->settings['base_url']}app=members&amp;module=warnings&amp;section=reasons", $this->lang->words['warn_reasons_del_saved'] );
		return;
	}
	
	private function reorder()
	{			
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
		$ajax		 = new $classToLoad();
		
		if( $this->registry->adminFunctions->checkSecurityKey( $this->request['md5check'], true ) === false )
		{
			$ajax->returnString( $this->lang->words['postform_badmd5'] );
			exit();
		}

 		$position	= 1;
 		
 		if( is_array($this->request['reasons']) AND count($this->request['reasons']) )
 		{
 			foreach( $this->request['reasons'] as $this_id )
 			{
 				$this->DB->update( 'members_warn_reasons', array( 'wr_order' => $position ), 'wr_id=' . $this_id );
 				
 				$position++;
 			}
 		}
 		
 		$ajax->returnString( 'OK' );
		return;
	}
}