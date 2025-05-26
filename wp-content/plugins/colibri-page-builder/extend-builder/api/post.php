<?php

namespace ExtendBuilder;

class PostApi {


	public function autoDraftDelete($req = []) {
		$uuid = isset($req['uuid']) ? $req['uuid'] : null;
		if(!$uuid) {
			return $this->error();
		}
		$postId = $this->find_changeset_post_id_for_auto_draft($uuid);
		if(!$postId) {
			return $this->error();
		}
		$result = wp_trash_post($postId);
		if(!$result) {
			return $this->error();
		}
		return $this->success();
	}

	private function success($data = []) {
		$data =  [
			'success' =>  true,
			'data' => $data
		];

		return  json_encode( $data );
	}

	private function error($data = []) {
		$data =  [
			'success' =>  false,
			'data' => $data
		];

		return  json_encode( $data );
	}

	private function find_changeset_post_id_for_auto_draft($uuid) {
		$cache_group       = 'customize_changeset_post';
		$changeset_post_id = wp_cache_get( $uuid, $cache_group );
		if ( $changeset_post_id && 'customize_changeset' === get_post_type( $changeset_post_id ) ) {
			return $changeset_post_id;
		}

		$changeset_post_query = new \WP_Query(
			array(
				'post_type'              => 'customize_changeset',
				'post_status'            => 'auto-draft',
				'name'                   => $uuid,
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'cache_results'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'lazy_load_term_meta'    => false,
			)
		);
		if ( ! empty( $changeset_post_query->posts ) ) {
			// Note: 'fields'=>'ids' is not being used in order to cache the post object as it will be needed.
			$changeset_post_id = $changeset_post_query->posts[0]->ID;
			wp_cache_set( $uuid, $changeset_post_id, $cache_group );
			return $changeset_post_id;
		}

		return null;
	}


}

Api::addEndPoint("post", new PostApi());



