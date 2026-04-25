<script setup lang="ts">
import type { HTMLAttributes } from "vue";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import {
  Field,
  FieldDescription,
  FieldGroup,
  FieldLabel,
} from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { FetchError } from "ofetch";

const props = defineProps<{
  class?: HTMLAttributes["class"];
}>();

const { login } = useSanctumAuth();

type LoginForm = {
  email?: string;
  password?: string;
};
const { data, errors, processing, setErrors } = useApiForm<LoginForm>({
  path: "/api/auth/login",
  initialData: {},
});
const onFormSubmit = async () => {
  processing.value = true;
  try {
    await login({
      email: data.email,
      password: data.password,
    });
  } catch (error) {
    if (error instanceof FetchError) {
      if (error.response?.status === 422) {
        setErrors(error.response?._data.errors);
      }
      if (error.response?.status === 401) {
        setErrors({
          email: [error.response?._data?.message ?? "Wrong email or password."],
        });
      }
    }
  } finally {
    processing.value = false;
  }
};
</script>

<template>
  <div :class="cn('flex flex-col gap-6', props.class)">
    <Card>
      <CardHeader>
        <CardTitle>Login to your account</CardTitle>
        <CardDescription>
          Enter your email below to login to your account
        </CardDescription>
      </CardHeader>
      <CardContent>
        <form @submit.prevent="onFormSubmit">
          <FieldGroup>
            <Field>
              <FieldLabel for="email">Email</FieldLabel>
              <Input
                id="email"
                v-model="data.email"
                :disabled="processing"
                type="email"
                placeholder="m@example.com"
                required
              />
              <FieldError v-if="!!errors.email">{{ errors.email }}</FieldError>
            </Field>
            <Field>
              <div class="flex items-center">
                <FieldLabel for="password">Password</FieldLabel>
                <a
                  href="#"
                  class="ml-auto inline-block text-sm underline-offset-4 hover:underline"
                >
                  Forgot your password?
                </a>
              </div>
              <Input
                id="password"
                v-model="data.password"
                :disabled="processing"
                type="password"
                required
              />
              <FieldError v-if="!!errors.password">{{
                errors.password
              }}</FieldError>
            </Field>
            <Field>
              <Button type="submit"> Login</Button>
              <!--              <Button variant="outline" type="button">-->
              <!--                Login with Google-->
              <!--              </Button>-->
              <FieldDescription class="text-center">
                Don't have an account?
                <NuxtLink href="/signup"> Sign up</NuxtLink>
              </FieldDescription>
            </Field>
          </FieldGroup>
        </form>
      </CardContent>
    </Card>
  </div>
</template>
