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
import { Input } from "@/components/ui/input";

const props = defineProps<{
  class?: HTMLAttributes["class"];
}>();

const { login } = useSanctumAuth();

type LoginForm = {
  email?: string;
  password?: string;
};
const { handleSubmit, setErrors, isSubmitting, setFieldError } =
  useForm<LoginForm>();

const onSubmit = handleSubmit(async (data) => {
  try {
    await login({ email: data.email, password: data.password });
  } catch (e: unknown) {
    const err = e as {
      response?: { status?: number; _data?: Record<string, unknown> };
    };
    if (err?.response?.status === 422) {
      setErrors(err.response?._data?.errors as Record<string, string[]>);
    }
    if (err?.response?.status === 401) {
      setFieldError("email", err.response?._data?.message as string);
    }
  }
});
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
        <form class="flex flex-col gap-4" @submit.prevent="onSubmit">
          <FormField v-slot="{ componentField }" name="email">
            <FormItem>
              <FormLabel>Email</FormLabel>
              <FormControl>
                <Input v-bind="componentField" />
              </FormControl>
              <FormMessage />
            </FormItem>
          </FormField>
          <FormField v-slot="{ componentField }" name="password">
            <FormItem>
              <FormLabel>Password</FormLabel>
              <FormControl>
                <Input v-bind="componentField" type="password" />
              </FormControl>
              <FormMessage />
            </FormItem>
          </FormField>
          <Button type="submit" :disabled="isSubmitting">Login</Button>
          <div class="text-center">
            Don't have an account?
            <NuxtLink href="/signup" class="hover:underline">Sign up</NuxtLink>
          </div>
        </form>
      </CardContent>
    </Card>
  </div>
</template>
