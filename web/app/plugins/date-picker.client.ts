export default defineNuxtPlugin(() => {
  document.addEventListener('click', (event) => {
    const target = event.target

    if (!(target instanceof HTMLInputElement) || target.type !== 'date' || target.disabled || target.readOnly) {
      return
    }

    try {
      target.showPicker()
    }
    catch {
      return
    }
  })
})
