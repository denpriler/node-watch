<script setup lang="ts">
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";

const { login } = useSanctumAuth();
const client = useSanctumClient();

type SignUpForm = {
  email?: string;
  password?: string;
  password_confirmation?: string;
};
const { handleSubmit, setErrors, isSubmitting } = useForm<SignUpForm>();

const onSubmit = handleSubmit(async (data) => {
  try {
    await client("/api/auth/register", { method: "POST", body: data });
    await login({ email: data.email, password: data.password });
  } catch (e: unknown) {
    const err = e as {
      response?: { status?: number; _data?: Record<string, unknown> };
    };
    if (err?.response?.status === 422) {
      setErrors(err.response?._data?.errors as Record<string, string[]>);
    }
  }
});
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Create an account</CardTitle>
      <CardDescription>
        Enter your information below to create your account
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
        <FormField v-slot="{ componentField }" name="password_confirmation">
          <FormItem>
            <FormLabel>Confirm Password</FormLabel>
            <FormControl>
              <Input v-bind="componentField" type="password" />
            </FormControl>
            <FormMessage />
          </FormItem>
        </FormField>
        <Button type="submit" :disabled="isSubmitting">Create Account</Button>
        <div class="px-6 text-center">
          Already have an account?
          <NuxtLink href="/login" class="hover:underline">Sign in</NuxtLink>
        </div>
      </form>
    </CardContent>
  </Card>
</template>
