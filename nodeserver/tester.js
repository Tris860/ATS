

const PHP_BACKEND_URL = 'http://localhost:3000/backend/main.php?action=is_current_time_in_period';
async function checkPhpBackend() {
  try {
    const resp = await fetch(PHP_BACKEND_URL);
    if (!resp.ok) throw new Error(`HTTP ${resp.status} ${resp.statusText}`);
    const ct = resp.headers.get('content-type') || '';
    if (!ct.includes('application/json')) throw new Error('Expected application/json');

    const data = await resp.text();
    console.log('PHP backend response:', data);
    /* if (data.success === true) {
      const messageToWemos = 'AUTO_ON';
      const messageToWeb = 'TIME_MATCHED: '+data.message+": "+ data.id;

      // Broadcast AUTO_ON to all connected Wemos
      console.log('Broadcasting to Wemos:', messageToWemos);
    } */
  } catch (err) {
    console.error('checkPhpBackend error:', err.message);
  }
}
setInterval(checkPhpBackend, 60000); // Check every 60 seconds