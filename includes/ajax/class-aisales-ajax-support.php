<?php
/**
 * Support AJAX Handlers
 *
 * Handles all support ticket-related AJAX actions including
 * draft creation, clarification, submission, and attachment uploads.
 *
 * @package AISales_Sales_Manager
 * @since 1.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Support AJAX Handlers class
 */
class AISales_Ajax_Support extends AISales_Ajax_Base {

	/**
	 * Maximum attachment file size in bytes (7MB)
	 *
	 * @var int
	 */
	private $max_attachment_size = 7340032;

	/**
	 * Register AJAX actions
	 */
	protected function register_actions() {
		$this->add_action( 'support_draft', 'handle_support_draft' );
		$this->add_action( 'support_clarify', 'handle_support_clarify' );
		$this->add_action( 'support_submit', 'handle_support_submit' );
		$this->add_action( 'support_list', 'handle_support_list' );
		$this->add_action( 'support_stats', 'handle_support_stats' );
		$this->add_action( 'support_upload', 'handle_support_upload' );
	}

	/**
	 * Handle support draft creation
	 */
	public function handle_support_draft() {
		$this->verify_request();

		$title       = $this->get_post( 'title', 'text' );
		$description = $this->get_post( 'description', 'textarea' );
		$category    = $this->get_post( 'category', 'key', 'support' );
		$attachments = $this->parse_attachments();

		if ( empty( $title ) || empty( $description ) ) {
			$this->error( __( 'Title and description are required.', 'ai-sales-manager-for-woocommerce' ) );
		}

		$result = $this->handle_api_result(
			$this->api()->create_support_draft( array(
				'title'       => $title,
				'description' => $description,
				'category'    => $category,
				'attachments' => $attachments,
			) )
		);

		$this->success( $result );
	}

	/**
	 * Handle support clarification
	 */
	public function handle_support_clarify() {
		$this->verify_request();

		$draft_id    = $this->require_post( 'draft_id', 'text', __( 'Draft ID is required.', 'ai-sales-manager-for-woocommerce' ) );
		$answers_raw = $this->get_post( 'answers', 'raw' );
		$answers     = is_array( $answers_raw ) ? $answers_raw : array();

		$result = $this->handle_api_result( $this->api()->clarify_support_draft( $draft_id, $answers ) );

		$this->success( $result );
	}

	/**
	 * Handle support ticket submission
	 */
	public function handle_support_submit() {
		$this->verify_request();

		$draft_id = $this->require_post( 'draft_id', 'text', __( 'Draft ID is required.', 'ai-sales-manager-for-woocommerce' ) );

		$result = $this->handle_api_result( $this->api()->submit_support_ticket( $draft_id ) );

		$this->success( $result );
	}

	/**
	 * Handle support ticket list
	 */
	public function handle_support_list() {
		$this->verify_request();

		$filters = array();

		$status = $this->get_post( 'status', 'key' );
		if ( ! empty( $status ) ) {
			$filters['status'] = $status;
		}

		$search = $this->get_post( 'search', 'text' );
		if ( ! empty( $search ) ) {
			$filters['search'] = $search;
		}

		$result = $this->handle_api_result( $this->api()->get_support_tickets( $filters ) );

		$this->success( $result );
	}

	/**
	 * Handle support stats
	 */
	public function handle_support_stats() {
		$this->verify_request();

		$result = $this->handle_api_result( $this->api()->get_support_stats() );

		$this->success( $result );
	}

	/**
	 * Handle support attachment upload
	 */
	public function handle_support_upload() {
		$this->verify_request();

		$nonce_value = isset( $_POST[ $this->nonce_field ] )
			? sanitize_text_field( wp_unslash( $_POST[ $this->nonce_field ] ) )
			: '';
		wp_verify_nonce( $nonce_value, $this->nonce_action );

		if ( empty( $_FILES['attachment'] ) ) {
			$this->error( __( 'No file uploaded.', 'ai-sales-manager-for-woocommerce' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$file_size = isset( $_FILES['attachment']['size'] ) ? absint( $_FILES['attachment']['size'] ) : 0;

		if ( $file_size > $this->max_attachment_size ) {
			$this->error( __( 'File exceeds 7MB limit.', 'ai-sales-manager-for-woocommerce' ) );
		}

		$attachment_id = media_handle_upload( 'attachment', 0 );

		if ( is_wp_error( $attachment_id ) ) {
			$this->error( $attachment_id->get_error_message() );
		}

		$this->success( array(
			'id'        => $attachment_id,
			'url'       => wp_get_attachment_url( $attachment_id ),
			'filename'  => get_the_title( $attachment_id ),
			'mime_type' => get_post_mime_type( $attachment_id ),
			'size'      => $file_size,
		) );
	}

	/**
	 * Parse attachments from POST data
	 *
	 * @return array Sanitized attachments array.
	 */
	private function parse_attachments() {
		$attachments_raw = $this->get_post( 'attachments', 'raw' );
		$attachments     = array();

		if ( empty( $attachments_raw ) ) {
			return $attachments;
		}

		$decoded = json_decode( $attachments_raw, true );

		if ( ! is_array( $decoded ) ) {
			return $attachments;
		}

		foreach ( $decoded as $attachment ) {
			if ( empty( $attachment['filename'] ) || empty( $attachment['url'] ) ) {
				continue;
			}

			$attachments[] = array(
				'filename'   => sanitize_file_name( $attachment['filename'] ),
				'mime_type'  => isset( $attachment['mime_type'] ) ? sanitize_text_field( $attachment['mime_type'] ) : 'application/octet-stream',
				'url'        => esc_url_raw( $attachment['url'] ),
				'size_bytes' => isset( $attachment['size_bytes'] ) ? absint( $attachment['size_bytes'] ) : 0,
			);
		}

		return $attachments;
	}
}
