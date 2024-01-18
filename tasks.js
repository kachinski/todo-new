document.getElementById('form').addEventListener('submit', handleSubmit);

async function handleSubmit(event) {
  const input = document.getElementById('input');

  event.preventDefault();

  try {
    const response = await fetch('tasks.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        task: input.value
      })
    });
    if (response.ok) {
      const res = await response.json();
      const li = document.createElement('li');
      const tasks = document.getElementById('tasks');
      const noTasks = document.getElementById('no-tasks');

      if (noTasks) {
        noTasks.remove();
      }

      input.value = '';

      li.innerHTML = `${res.task}&nbsp;<a href="?del=${res.id}">Delete</a>`;
      tasks.appendChild(li);
    } else {
      console.error('Failed to add task.');
    }
  } catch (error) {
    console.error(error);
  }
}
