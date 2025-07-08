// // @ts-check
// const { test, expect } = require('@playwright/test')

// async function login (page) {
//   await page.goto('/wp-admin')
//   await page.fill('#user_login', 'testUserWP')
//   await page.fill('#user_pass', 'password_748932A')
//   await page.click('[name="wp-submit"]')

//   // Click the Confirm-email button, if it exists
//   try {
//     await page.waitForSelector('button:has-text("Bestätigen")', { timeout: 5000 });
//     await page.click('button:has-text("Bestätigen")');
//   } catch (error) {
//     // Silence is golden.
//   }
// }

// test.skip('themesnotification', async ({ page }) => {
//   await login(page)

//   await page.goto( '/wp-admin/themes.php')
//   expect(await page.textContent('text=Themes')).toBeTruthy()
//   expect(await page.textContent('text=Der Sicherheits-Scan ergab kritische Probleme')).toBeTruthy()
//   expect(await page.textContent('text=Twenty Twenty-Three')).toBeTruthy()
//   await page.click('a:has-text("Hier erhältst du weitere Informationen zum weiteren Vorgehen")');

//   await page.click('button:has-text("Löschen")');
// })

