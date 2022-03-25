<?php
if (isset($_POST['exp'])){ 
        
		global $wpdb;

		$sql = "SELECT P.ID, P.post_author, P.post_title, P.post_status,P.post_content, PM.meta_value FROM {$wpdb->prefix}posts P inner join {$wpdb->prefix}postmeta PM on P.ID = PM.post_id WHERE P.post_type = 'sainstocknotifier' and PM.meta_key = 'smsalert_instock_pid'";

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : ' DESC';
		} else {
			$sql .= ' ORDER BY post_date desc';
		}

		// $sql .= " LIMIT $per_page";
		// $sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;

		
	  
        $filename = 'All Subscriber' . time() . '.csv';
        $header_row = array(
            'ID',
            'post_author',
            'Mobile_Number',
            'Status',
            'Register user',
           'post_content',

        );
       $data_rows = array();


       $result = $wpdb->get_results( $sql, 'ARRAY_A' );
        foreach ( $result as $user ) 
        {
            $row = array(
            $user['ID'],
            $user['post_author'],
            $user['post_title'],
            $user['post_status'],
            $user['post_content'],
           
            );
            $data_rows[] = $row;
        }
        ob_end_clean ();
        $fh = @fopen( 'php://output', 'w' );
        header( "Content-Disposition: attachment; filename={$filename}" );
        fputcsv( $fh, $header_row );
        foreach ( $data_rows as $data_row ) 
        {
            fputcsv( $fh, $data_row );
        }
    
        
        exit();
    }
?>
