import type { FetchError } from "ofetch";

type UseApiFormConfig<T extends object> = {
  path: string;
  initialData: T;
};

type FormErrors<T> = Partial<Record<keyof T, string>>;

export function useApiForm<T extends object>(config: UseApiFormConfig<T>) {
  const client = useSanctumClient();

  const data = reactive<T>({ ...config.initialData }) as T;
  const errors = ref<FormErrors<T>>({});
  const processing = ref(false);

  function setErrors(rawErrors: Record<string, string[]>) {
    errors.value = Object.fromEntries(
      Object.entries(rawErrors).map(([field, messages]) => [
        field,
        messages[0],
      ]),
    ) as FormErrors<T>;
  }

  function clearErrors(...fields: (keyof T)[]) {
    if (fields.length === 0) {
      errors.value = {};
    } else {
      // eslint-disable-next-line @typescript-eslint/no-dynamic-delete
      fields.forEach((field) => delete errors.value[field]);
    }
  }

  Object.keys(config.initialData).forEach((field) => {
    watch(
      () => (data as Record<string, unknown>)[field],
      () => clearErrors(field as keyof T),
    );
  });

  function reset() {
    Object.assign(data, config.initialData);
    clearErrors();
  }

  async function submit<R = unknown>(
    method: "get" | "post" | "put" | "patch" | "delete",
    onSuccess?: (response: R) => void,
  ) {
    processing.value = true;
    clearErrors();

    try {
      const response = await client<R>(config.path, {
        method,
        body: method !== "get" ? data : undefined,
      });

      onSuccess?.(response);
    } catch (err) {
      const fetchError = err as FetchError;

      if (fetchError.status === 422 && fetchError.data?.errors) {
        setErrors(fetchError.data.errors as Record<string, string[]>);
        return;
      }

      throw err;
    } finally {
      processing.value = false;
    }
  }

  return { data, errors, processing, submit, reset, clearErrors, setErrors };
}
