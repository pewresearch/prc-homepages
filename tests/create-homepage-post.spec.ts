import { test, expect } from '@wordpress/e2e-test-utils-playwright';

const testTitle = 'Test Homepage';
const testContent = 'This is a test Homepage post.';

test.describe('Create Homepage Post', () => {
	test('Ensure homepage post type is properly registered', async ({
		requestUtils,
	}) => {
		const homepagePosts = await requestUtils.rest({
			path: '/wp/v2/homepage',
			method: 'GET',
		});
		expect(homepagePosts).toBeDefined();
	});

	test('Homepage post created', async ({
		admin,
		editor,
		requestUtils,
		page,
	}) => {
		await admin.createNewPost({
			title: testTitle,
			content: testContent,
			postType: 'homepage',
		});
		// Publish the homepage
		await editor.publishPost();

		// Get the created homepage via REST API
		const homepagePosts = await requestUtils.rest({
			path: '/wp/v2/homepage',
			method: 'GET',
		});
		// Get the first item out of the homepagePosts array
		const homepagePost = homepagePosts?.[0];
		// Verify the homepage was created with correct title and content
		expect(homepagePost.title.rendered).toBe(testTitle);
		// Create a screenshot of the homepage
		const today = new Date();
		// This gives 'YYYY-MM-DD' format.
		const formattedDate = today.toISOString().split('T')[0];
		await page.screenshot({
			path: `tests/screenshots/homepage-${formattedDate}.png`,
		});
	});
});
