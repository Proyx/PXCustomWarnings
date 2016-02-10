<?php

class PXCustomWarnings_warnings extends public_members_profile_warnings {
	public function save()
	{
	
		$points = 0;
		$mq = 0;
		$mq_unit = 'd';
		$rpa = 0;
		$rpa_unit = 'd';
		$suspend = 0;
		$suspend_unit = 'd';
		$banGroup = 0;
		$removePoints = 0;
		$removePointsUnit = 'd';
		
		$errors = array();
		
		if ( $this->request['reason'] === '' )
		{
			$errors['reason'] = $this->lang->words['warnings_err_reason'];
		}
		else
		{
			$reason = intval( $this->request['reason'] );
			
			if ( !$reason )
			{
				if ( !$this->memberData['g_access_cp'] and !$this->settings['warnings_enable_other'] )
				{
					$errors['reason'] = $this->lang->words['warnings_err_reason'];
				}
				else
				{
					$points = floatval( $this->request['points'] );
					$removePoints = intval( $this->request['remove'] );
					$removePointsUnit = $this->request['remove_unit'] == 'h' ? 'h' : 'd';
				}
			}
			else
			{
				$reason = $this->reasons[ $reason ];
				
				if ( !$reason['wr_id'] )
				{
					$errors['reason'] = $this->lang->words['warnings_err_reason'];
				}
				else
				{
					if ( $this->memberData['g_access_cp'] or $reason['wr_points_override'] )
					{
						ipsRegistry::DB()->build( array( 'select' => '*',
							'from' => 'members_warn_logs',
							'where' => 'wl_member='.$this->request['member'],
							'where' => 'wl_reason='.$reason['wr_id']) );
							
						$warnes = ipsRegistry::DB()->execute();
						$vezesWarnados = 0;
						
						
						while( $w = $this->DB->fetch( $warnes ) ) {
							$vezesWarnados = $vezesWarnados + 1;
						}
						
						$warnes = ipsRegistry::DB()->buildAndFetch( array( 'select' => '*',
							'from' => 'members_warn_reasons',
							'where' => 'wr_id='.$reason['wr_id']) );
						
						$w = explode(', ', $warnes['wr_points']);
						if(count($w) - 1 < $vezesWarnados) {
							$size = count($w);
							$points = $w[$size - 1];
						} else {
							$points = $w[$vezesWarnados];
						}
					}
					else
					{
						ipsRegistry::DB()->build( array( 'select' => '*',
							'from' => 'members_warn_logs',
							'where' => 'wl_member='.$this->request['member'],
							'where' => 'wl_reason='.$reason['wr_id']) );
							
						$warnes = ipsRegistry::DB()->execute();
						$vezesWarnados = 0;
						
						
						while( $w = $this->DB->fetch( $warnes ) ) {
							$vezesWarnados = $vezesWarnados + 1;
						}
						
						$warnes = ipsRegistry::DB()->buildAndFetch( array( 'select' => '*',
							'from' => 'members_warn_reasons',
							'where' => 'wr_id='.$reason['wr_id']) );
						
						$w = explode(', ', $warnes['wr_points']);
						if(count($w) - 1 < $vezesWarnados) {
							$size = count($w);
							$points = $w[$size - 1];
						} else {
							$points = $w[$vezesWarnados];
						}
					}
					
					if ( $this->memberData['g_access_cp'] or $reason['wr_remove_override'] )
					{
						$removePoints = intval( $this->request['remove'] );
						$removePointsUnit = $this->request['remove_unit'] == 'h' ? 'h' : 'd';
					}
					else
					{
						$removePoints = intval( $reason['wr_remove'] );
						$removePointsUnit = $reason['wr_remove_unit'];
					}
				}
				
				$reason = $reason['wr_id'];
			}
			
			$newPointLevel = floatval( $this->_member['warn_level'] + $points );
			$action = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'members_warn_actions', 'where' => "wa_points<={$newPointLevel}", 'order' => 'wa_points DESC', 'limit' => 1 ) );
			
			if ( $action )
			{
				if ( $action['wa_override'] )
				{
					$mq = $this->request['mq_perm'] ? -1 : intval( $this->request['mq'] );
					$mq_unit = $this->request['mq_unit'];
					$rpa = $this->request['rpa_perm'] ? -1 : intval( $this->request['rpa'] );
					$rpa_unit = $this->request['rpa_unit'];
					$suspend = $this->request['suspend_perm'] ? -1 : intval( $this->request['suspend'] );
					$suspend_unit = $this->request['suspend_unit'];
					$banGroup = $this->request['ban_group'] ? intval( $this->request['ban_group_id'] ) : 0;
				}
				else
				{
					$mq = intval( $action['wa_mq'] );
					$mq_unit = $action['wa_mq_unit'];
					$rpa = intval( $action['wa_rpa'] );
					$rpa_unit = $action['wa_rpa_unit'];
					$suspend = intval( $action['wa_suspend'] );
					$suspend_unit = $action['wa_suspend_unit'];
					$banGroup = intval( $action['wa_ban_group'] );
				}
			}
			else
			{
				if ( $this->memberData['g_access_cp'] or $this->settings['warning_custom_noaction'] )
				{
					$mq = $this->request['mq_perm'] ? -1 : intval( $this->request['mq'] );
					$mq_unit = $this->request['mq_unit'];
					$rpa = $this->request['rpa_perm'] ? -1 : intval( $this->request['rpa'] );
					$rpa_unit = $this->request['rpa_unit'];
					$suspend = $this->request['suspend_perm'] ? -1 : intval( $this->request['suspend'] );
					$suspend_unit = $this->request['suspend_unit'];
					$banGroup = $this->request['ban_group'] ? intval( $this->request['ban_group_id'] ) : 0;
				}
				else
				{
				}
			}
		}
		
		if ( !empty( $errors ) )
		{
			return $this->form( $errors );
		}
		
		
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$editor = new $classToLoad();
		
		$noteForMember = $editor->process( $_POST['note_member'] );
		$noteForMods   = $editor->process( $_POST['note_mods'] );
				
		
		$expireDate = 0;
		if ( $removePoints )
		{
			IPSTime::setTimestamp( time() );
			if ( $removePointsUnit == 'h' )
			{
				IPSTime::add_hours( $removePoints );
			}
			else
			{
				IPSTime::add_days( $removePoints );
			}
			$expireDate = IPSTime::getTimestamp();
		}
				
		$warning = array(
			'wl_member'			=> $this->_member['member_id'],
			'wl_moderator'		=> $this->memberData['member_id'],
			'wl_date'			=> time(),
			'wl_reason'			=> $reason,
			'wl_points'			=> $points,
			'wl_note_member'	=> $noteForMember,
			'wl_note_mods'		=> $noteForMods,
			'wl_mq'				=> $mq,
			'wl_mq_unit'		=> $mq_unit,
			'wl_rpa'			=> $rpa,
			'wl_rpa_unit'		=> $rpa_unit,
			'wl_suspend'		=> $suspend,
			'wl_suspend_unit'	=> $suspend_unit,
			'wl_ban_group'		=> $banGroup,
			'wl_expire'			=> $removePoints,
			'wl_expire_unit'	=> $removePointsUnit,
			'wl_acknowledged'	=> ( $this->settings['warnings_acknowledge'] ? 0 : 1 ),
			'wl_content_app'	=> trim( $this->request['from_app'] ),
			'wl_content_id1'	=> $this->request['from_id1'],
			'wl_content_id2'	=> $this->request['from_id2'],
			'wl_expire_date'	=> $expireDate,
			);
		
		$warning['actionData']  = $action;
		$warning['reasonsData'] = $this->reasons;
		IPSLib::doDataHooks( $warning, 'memberWarningPre' );
		unset( $warning['actionData'], $warning['reasonsData'] );
		
		$this->DB->insert( 'members_warn_logs', $warning );
		$warning['wl_id'] = $this->DB->getInsertId();
		
		$warning['actionData']  = $action;
		$warning['reasonsData'] = $this->reasons;
		IPSLib::doDataHooks( $warning, 'memberWarningPost' );
		unset( $warning['actionData'], $warning['reasonsData'] );
		
		$update = array();
		
		if ( $points )
		{
			$update['warn_level'] = $this->_member['warn_level'] + $points;
		}
		
		if ( $mq )
		{
			$update['mod_posts'] = ( $mq == -1 ? 1 : IPSMember::processBanEntry( array( 'unit' => $mq_unit, 'timespan' => $mq ) ) );
		}
		if ( $rpa )
		{
			$update['restrict_post'] = ( $rpa == -1 ? 1 : IPSMember::processBanEntry( array( 'unit' => $rpa_unit, 'timespan' => $rpa ) ) );
		}
		if ( $suspend )
		{
			if ( $suspend == -1 )
			{
				$update['member_banned'] = 1;
			}
			else
			{
				$update['temp_ban'] = IPSMember::processBanEntry( array( 'unit' => $suspend_unit, 'timespan' => $suspend ) );
			}
		}
		
		if ( $banGroup > 0 )
		{
			if ( ! $this->caches['group_cache'][$banGroup]['g_access_cp'] AND ! $this->caches['group_cache'][$banGroup]['g_is_supmod'] AND $banGroup != $this->settings['guest_group'] )
			{
				$update['member_group_id'] = $banGroup;
			}
		}
		
		if ( $this->settings['warnings_acknowledge'] )
		{
			$update['unacknowledged_warnings'] = 1;
		}
		
		if ( !empty( $update ) )
		{
			IPSMember::save( $this->_member['member_id'], array( 'core' => $update ) );
		}
		
		if ( $warning['wl_content_app'] and IPSLib::appIsInstalled( $warning['wl_content_app'] ) )
		{
			$file = IPSLib::getAppDir( $warning['wl_content_app'] ) . '/extensions/warnings.php';
			
			if ( is_file( $file ) )
			{
				$classToLoad = IPSLib::loadLibrary( $file, 'warnings_' . $warning['wl_content_app'], $warning['wl_content_app'] );
				
				if ( class_exists( $classToLoad ) and method_exists( $classToLoad, 'getContentUrl' ) )
				{
					$object = new $classToLoad();
					$content = $object->getContentUrl( $warning );
				}
			}
		}
		
		$classToLoad		= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
		$notifyLibrary		= new $classToLoad( $this->registry );
		
		if ( $this->settings['warnings_acknowledge'] OR $noteForMember )
		{
			try
			{
				$notifyLibrary->setMember( $this->_member );
				$notifyLibrary->setFrom( $this->memberData );
				$notifyLibrary->setNotificationKey( 'warning' );
				$notifyLibrary->setNotificationUrl( "app=members&module=profile&section=warnings&member={$this->_member['member_id']}" );
				$notifyLibrary->setNotificationTitle( sprintf( $this->lang->words['warnings_notify'], $this->registry->output->buildUrl( "app=members&module=profile&section=warnings&member={$this->_member['member_id']}" ) ) );
				$notifyLibrary->setNotificationText( sprintf(
					$this->lang->words['warnings_notify_text'],
					$this->_member['members_display_name'],
					$this->memberData['members_display_name'],
					$reason ? $this->reasons[ $reason ]['wr_name'] : $this->lang->words['warnings_reasons_other'],
					$noteForMember ? sprintf( $this->lang->words['warnings_notify_member_note'], $noteForMember ) : '',
					$this->settings['warn_show_own'] ? sprintf( $this->lang->words['warnings_notify_view_link'], $this->registry->output->buildUrl( "app=members&module=profile&section=warnings&member={$this->_member['member_id']}" ) ) : ''
					) );
				$notifyLibrary->sendNotification();
			}
			catch ( Exception $e ) {}
		}
		
		$mods = array();
		$mids = array();
		$gids = array();
		$canWarnMids = array();
		$canWarnGids = array();
		
		$this->DB->build( array( 'select' => 'member_id, allow_warn',
								 'from'   => 'moderators',
								 'where'  => 'is_group=0' ) );
								 
		$this->DB->execute();
		while ( $row = $this->DB->fetch() )
		{
			$mids[ $row['member_id'] ] = $row['member_id'];
			
			if ( $row['allow_warn'] )
			{
				$canWarnMids[] = $row['member_id'];
			}
		}
		
		$this->DB->build( array( 'select' => 'group_id',
								 'from'   => 'moderators',
								 'where'  => 'is_group=1 AND allow_warn=1' ) );
								 
		$this->DB->execute();
		while ( $row = $this->DB->fetch() )
		{
			$gids[]        = $row['group_id'];
			$canWarnGids[] = $row['group_id'];
		}
		
		foreach( $this->caches['group_cache'] as $id => $row )
		{
			if ( $row['g_is_supmod'] )
			{
				$gids[] = $row['g_id'];
			}
		}
		
		if ( count( $gids ) )
		{
			$this->DB->build( array( 'select' => 'member_id',
									 'from'   => 'members',
									 'where'  => 'member_group_id IN (' . implode( ',', $gids ) . ')',
									 'limit'  => array( 0, 750 ) ) );
									 
			$this->DB->execute();
			while ( $row = $this->DB->fetch() )
			{
				$mids[ $row['member_id'] ] = $row['member_id'];
			}
		}
	
		$_mods = IPSMember::load( $mids, 'all' );
		
		if ( count( $_mods ) )
		{
			foreach( $_mods as $id => $row )
			{
				if ( $row['member_id'] == $this->memberData['member_id'] )
				{
					continue;
				}
				
				if ( $row['g_is_supmod'] OR ( in_array( $row['member_id'], $canWarnMids ) ) OR ( in_array( $row['member_group_id'], $canWarnGids ) ) )
				{
					$mods[ $row['member_id'] ] = $row;
				}
			}
		}

		if ( count( $mods ) )
		{
			$notifyLibrary		= new $classToLoad( $this->registry );
			$notifyLibrary->setMultipleRecipients( $mods );
			$notifyLibrary->setFrom( $this->memberData );
			$notifyLibrary->setNotificationKey( 'warning_mods' );
			$notifyLibrary->setNotificationUrl( "app=members&module=profile&section=warnings&member={$this->_member['member_id']}" );
			$notifyLibrary->setNotificationTitle( sprintf(
				$this->lang->words['warnings_notify_mod'],
				$this->_member['members_display_name'],
				$this->registry->output->buildUrl( "app=members&module=profile&section=warnings&member={$this->_member['member_id']}" ),
				$this->memberData['members_display_name']
				) );
			$notifyLibrary->setNotificationText( sprintf(
				$this->lang->words['warnings_notify_text_mod'],
				$this->_member['members_display_name'],
				$this->memberData['members_display_name'],
				$this->registry->output->buildUrl( "app=members&module=profile&section=warnings&member={$this->_member['member_id']}" )
				) );
				
			try
			{
				$notifyLibrary->sendNotification();
			} catch ( Exception $e ) { }
		}
		
		
		if ( empty( $content['url'] ) )
		{
			$this->registry->getClass('output')->redirectScreen( $this->lang->words['warnings_done'] , $this->settings['base_url'] . 'app=members&amp;module=profile&amp;section=warnings&amp;member=' . $this->_member['member_id'] );
		}
		else
		{
			$this->registry->getClass('output')->redirectScreen( $this->lang->words['warnings_done'] , $content['url'] );
		}
		return;
	}
}