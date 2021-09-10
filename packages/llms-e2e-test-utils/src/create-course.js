import { createPost } from './create-post';

/**
 * Create and publish a new course
 *
 * @since Unknown
 * @param  title
 * @since 2.2.0 Use `createPost()`.
 * @param  string title Course title.
 * @return int The created course's WP_Post ID.
 */
export async function createCourse( title = 'Test Course' ) {
	return createPost( 'course', title );
}
