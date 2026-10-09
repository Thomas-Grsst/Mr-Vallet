export const useActionNotice = () => {
  const notice = ref<string | null>(null)
  let timer: ReturnType<typeof setTimeout> | undefined

  const notify = (message: string) => {
    notice.value = message
    clearTimeout(timer)
    timer = setTimeout(() => {
      notice.value = null
    }, 5000)
  }

  onBeforeUnmount(() => clearTimeout(timer))

  return { notice, notify }
}
